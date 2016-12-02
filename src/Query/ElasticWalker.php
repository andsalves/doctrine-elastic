<?php

namespace DoctrineElastic\Query;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\HavingClause;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\OrderByItem;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use DoctrineElastic\Elastic\ElasticQuery;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Mapping\Type;
use DoctrineElastic\Query\Walker\Helper\WalkerHelper;
use DoctrineElastic\Query\Walker\WhereWalker;

class ElasticWalker {

    /** @var ElasticQuery */
    protected $query;

    /** @var ElasticExecutor */
    private $executor;

    /** @var string */
    private $_className;

    /** @var WalkerHelper */
    private $walkerHelper;

    public function __construct(ElasticQuery $query) {
        $this->query = $query;
        $this->walkerHelper = new WalkerHelper();
    }

    public function getExecutor(SelectStatement $AST) {
        if (!$this->executor) {
            $searchParams = $this->walkSelectStatement($AST);
            $this->executor = new ElasticExecutor($searchParams, $AST, $this->_className);
        }

        return $this->executor;
    }

    /**
     * @param SelectStatement $AST
     * @return SearchParams
     */
    private function walkSelectStatement(SelectStatement $AST) {
        $searchParams = new SearchParams();
        $size = $this->query->getMaxResults();
        $offset = $this->query->getFirstResult();
        $this->_className = $this->getRootClass($AST);

        $this->walkSelectClause($AST->selectClause, $searchParams);
        $this->walkFromClause($AST->fromClause, $searchParams);

        if ($AST->whereClause) {
            $this->walkWhereClause($AST->whereClause, $searchParams);
        }

        if ($AST->groupByClause) {
            $this->walkGroupByClause($AST->groupByClause, $searchParams);
        }

        if ($AST->havingClause) {
            $this->walkHavingClause($AST->havingClause, $searchParams);
        }

        if ($AST->orderByClause) {
            $this->walkOrderByClause($AST->orderByClause, $searchParams);
        }

        $searchParams->setSize($size);
        $searchParams->setFrom($offset);

        return $searchParams;
    }

    private function walkSelectClause(SelectClause $selectClause, SearchParams $searchParams) {
//        /** @var SelectExpression $selectExpressions */
//        $selectExpressions = $selectClause->selectExpressions;
    }

    private function walkFromClause(FromClause $fromClause, SearchParams $searchParams) {
        $type = $this->getEntityElasticType($this->_className);

        $searchParams->setType($type->getName());
        $searchParams->setIndex($type->getIndex());
    }

    private function walkWhereClause(WhereClause $whereClause, SearchParams $searchParams) {
        $whereWalker = new WhereWalker($this->query, $this->_className, $this->walkerHelper);
        $whereWalker->walk($whereClause, $searchParams);
    }

    private function walkGroupByClause(GroupByClause $groupByClause, SearchParams $searchParams) {

    }

    private function walkHavingClause(HavingClause $havingClause, SearchParams $searchParams) {

    }

    private function walkOrderByClause(OrderByClause $orderByClause, SearchParams $searchParams) {
        /** @var OrderByItem $item */
        foreach ($orderByClause->orderByItems as $item) {
            $order = $item->type;
            $colunmName = $item->expression->field;
            $column = $this->getEntityColumn($colunmName, $this->_className);

            $searchParams->setSort([$column->name => strtolower($order)]);
        }
    }

    /**
     * @param $className
     * @return Type
     */
    private function getEntityElasticType($className) {
        $classMetadata = $this->query->getEntityManager()->getClassMetadata($className);

        $entityPersister = $this->query->getEntityManager()->getUnitOfWork()
            ->getEntityPersister($className);
        /** @var Type $type */
        $type = $entityPersister->getAnnotionReader()
            ->getClassAnnotation($classMetadata->getReflectionClass(), Type::class);

        return $type;
    }

    /**
     * @param $propertyName
     * @param $className
     * @return Column
     */
    private function getEntityColumn($propertyName, $className) {
        $classMetadata = $this->query->getEntityManager()->getClassMetadata($className);

        $entityPersiter = $this->query->getEntityManager()->getUnitOfWork()
            ->getEntityPersister($className);
        /** @var Column $type */
        $column = $entityPersiter->getAnnotionReader()
            ->getPropertyAnnotation($classMetadata->getReflectionProperty($propertyName), Column::class);

        return $column;
    }

    /**
     * @param SelectStatement $AST
     *
     * @return string
     */
    private function getRootClass(SelectStatement $AST) {
        /** @var \Doctrine\ORM\Query\AST\IdentificationVariableDeclaration[] $identificationVariableDeclarations */
        $identificationVariableDeclarations = $AST->fromClause->identificationVariableDeclarations;

        foreach ($identificationVariableDeclarations as $idVarDeclaration) {
            if ($idVarDeclaration->rangeVariableDeclaration->isRoot) {
                return $idVarDeclaration->rangeVariableDeclaration->abstractSchemaName;
            }
        }

        return (reset($identificationVariableDeclarations))->rangeVariableDeclaration->abstractSchemaName;
    }
}