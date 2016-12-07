<?php

namespace DoctrineElastic\Listener;

use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinColumns;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectStatement;
use DoctrineElastic\Decorators\ElasticEntityManager;
use DoctrineElastic\Elastic\ElasticQuery;
use DoctrineElastic\Elastic\ElasticQueryBuilder;
use DoctrineElastic\Event\QueryEventArgs;
use DoctrineElastic\Hydrate\AnnotationEntityHydrator;

class QueryListener {

    /** @var AnnotationEntityHydrator */
    protected $hydrator;

    private $fieldRel;
    private $fieldOrigin;

    public function __construct() {
        $this->hydrator = new AnnotationEntityHydrator();
    }

    public function beforeQuery(QueryEventArgs $eventArgs) {

    }

    public function postQuery(QueryEventArgs $eventArgs) {
        $results = $eventArgs->getResults();
        $AST = $eventArgs->getAST();
//        print '<pre>';
//
//        print_r($AST);
        if (!empty($results) && !is_null($AST)) {
            $this->executeRelationshipQueries($eventArgs->getQuery(), $AST, $results);
//            $this->executeJoinQueries($eventArgs->getQuery(), $AST, $results);
            $eventArgs->setResults($results);
        }

//        die;
    }

    private function executeRelationshipQueries($entity, SelectStatement $AST, array &$results) {
        /** @var ManyToOne[] $manyToOnes */
        $manyToOnes = $this->hydrator->extractSpecAnnotations($entity, ManyToOne::class);
        /** @var JoinColumns[] $joinsColumns */
        $joinsColumns = $this->hydrator->extractSpecAnnotations($entity, JoinColumns::class);

        foreach ($manyToOnes as $propName => $mto) {
            if (!isset($joinsColumns[$propName])) {
                continue;
            }

            /** @var JoinColumn $joinColumns */
            foreach ($joinsColumns as $joinColumns) {
                $fieldName = $joinColumns->referencedColumnName;
            }
        }

    }

    /**
     * @param ElasticQuery $parentQuery
     * @param SelectStatement $AST
     * @param array $results
     *
     */
    private function executeJoinQueries(ElasticQuery $parentQuery, SelectStatement $AST, array &$results) {
        /** @var IdentificationVariableDeclaration[] $idVariableDeclarations */
        $idVariableDeclarations = $AST->fromClause->identificationVariableDeclarations;

        foreach ($idVariableDeclarations as $vdeclaration) {
            /** @var Join[] $joins */
            $joins = $vdeclaration->joins;
            $parentAlias = $vdeclaration->rangeVariableDeclaration->aliasIdentificationVariable;

            foreach ($joins as $join) {
                $joinQueryBuilder = $this->convertJoinToQueryBuilder(
                    $join, $parentQuery->getEntityManager(), $results, $parentAlias
                );

                if ($this->fieldRel && $this->fieldOrigin) {
                    $joinResults = $joinQueryBuilder->getQuery()->getResult();
                    $joinResultsAssoc = [];

                    foreach ($joinResults as $joinResult) {
                        $joinValue = $this->hydrator->extract($joinResult, $this->fieldRel);
                        if ($joinValue) {
                            $joinResultsAssoc[$joinValue] = $joinResult;
                        }
                    }

                    foreach ($results as $i => $result) {
                        $originValue = $this->hydrator->extract($result, $this->fieldOrigin);
                        if ($originValue && isset($joinResultsAssoc[$originValue])) {
                            $this->hydrator->hydrate($result, [$this->fieldOrigin => $joinResultsAssoc[$originValue]]);
                            $results[$i] = $result;
                        }
                    }
                }
            }
        }
    }

    private function convertJoinToQueryBuilder(
        Join $join, ElasticEntityManager $entityManager, array $results, $parentAlias
    ) {
        $assocDeclaration = $join->joinAssociationDeclaration;


        if ($assocDeclaration instanceof RangeVariableDeclaration) {
            $conditionalExpression = $join->conditionalExpression;

            if ($conditionalExpression instanceof ConditionalPrimary) {
                $scp = $conditionalExpression->simpleConditionalExpression;

                $joinQueryBuilder = new ElasticQueryBuilder($entityManager);
                $joinedClass = $assocDeclaration->abstractSchemaName;
                $alias = $assocDeclaration->aliasIdentificationVariable;
                $joinQueryBuilder->select($alias);
                $joinQueryBuilder->from($joinedClass, $alias);
                $joinQueryBuilder->setMaxResults(1);
                $this->fieldRel = $this->fieldOrigin = null;

                if ($scp instanceof ComparisonExpression) {
                    if ($scp->leftExpression instanceof ArithmeticExpression) {
                        $saeLeft = $scp->leftExpression->simpleArithmeticExpression;
                        $saeRight = $scp->rightExpression->simpleArithmeticExpression;

                        if ($saeLeft instanceof PathExpression && $saeLeft->identificationVariable == $alias) {
                            $this->fieldRel = $saeLeft->field;
                        }

                        if ($saeRight instanceof PathExpression && $saeRight->identificationVariable == $alias) {
                            $this->fieldRel = $saeRight->field;
                        }

                        if ($saeLeft instanceof PathExpression && $saeLeft->identificationVariable == $parentAlias) {
                            $this->fieldOrigin = $saeLeft->field;
                        }

                        if ($saeRight instanceof PathExpression && $saeRight->identificationVariable == $parentAlias) {
                            $this->fieldOrigin = $saeRight->field;
                        }
                    }
                }

                if (is_string($this->fieldRel) && is_string($this->fieldOrigin)) {
                    $exp = $joinQueryBuilder->expr();
                    $hasCondition = false;
                    foreach ($results as $result) {
                        $fieldValue = $this->hydrator->extract($result, $this->fieldOrigin);
                        if (boolval($fieldValue)) {
                            $hasCondition = true;
                            $joinQueryBuilder->orWhere($exp->eq("$alias.$this->fieldRel", $exp->literal($fieldValue)));
                        }
                    }

                    if ($hasCondition) {
                        return $joinQueryBuilder;
                    }
                }
            }
        }

        return null;
    }
}