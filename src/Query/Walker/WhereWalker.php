<?php

namespace DoctrineElastic\Query\Walker;

use Doctrine\DBAL\Query\QueryException;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\BetweenExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\InExpression;
use Doctrine\ORM\Query\AST\LikeExpression;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\NullComparisonExpression;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\WhereClause;
use DoctrineElastic\Elastic\ElasticQuery;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Exception\InvalidOperatorException;
use DoctrineElastic\Exception\InvalidParamsException;
use DoctrineElastic\Hydrate\AnnotationEntityHydrator;
use DoctrineElastic\Mapping\Field;
use DoctrineElastic\Mapping\MetaField;
use DoctrineElastic\Query\Walker\Helper\WalkerHelper;

/**
 * Walker specialist for Where Clause of Query(builder)
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class WhereWalker {

    /** @var ElasticQuery */
    private $query;

    /** @var string */
    private $className;

    /** @var WalkerHelper */
    private $walkerHelper;

    /** @var AnnotationEntityHydrator */
    private $hydrator;

    private $fieldAnnotations = [];

    public function __construct(ElasticQuery $query, $className, WalkerHelper $walkerHelper) {
        $this->query = $query;
        $this->className = $className;
        $this->walkerHelper = $walkerHelper;
        $this->hydrator = new AnnotationEntityHydrator();

        $fieldAnnotations = $this->hydrator->extractSpecAnnotations($className, Field::class);
        $metaFieldAnnotations = $this->hydrator->extractSpecAnnotations($className, MetaField::class);

        $this->fieldAnnotations = array_merge($fieldAnnotations, $metaFieldAnnotations);
    }

    private function fetchTermsAndFactors(Node $node) {
        $terms = $factors = [];

        switch (get_class($node)) {
            case ConditionalTerm::class:
                /** @var ConditionalTerm $node */
                $conditionalFactors = $node->conditionalFactors;
                foreach ($conditionalFactors as $conditionalFactor) {
                    $factors[] = $this->fetchTermsAndFactors($conditionalFactor);
                }
                break;
            case ConditionalPrimary::class:
                /** @var ConditionalPrimary $node */
                if ($node->isSimpleConditionalExpression()) {
                    $factors[] = $node->simpleConditionalExpression;
                } else {
                    $termsAndFactors = $this->fetchTermsAndFactors($node->conditionalExpression);
                    $terms = $termsAndFactors['terms'];
                    $factors = $termsAndFactors['factors'];
                }
                break;
            case ConditionalExpression::class:
                /** @var ConditionalExpression $node */
                $conditionalTerms = $node->conditionalTerms;
                foreach ($conditionalTerms as $conditionalTerm) {
                    $terms[] = $this->fetchTermsAndFactors($conditionalTerm);
                }
                break;
            default:
                $factors[] = $node;
        }

        return compact('factors', 'terms');
    }

    public function walk(WhereClause $whereClause, SearchParams $searchParams) {
        $conditionalExpr = $whereClause->conditionalExpression;

        $termsAndFactors = ($this->fetchTermsAndFactors($conditionalExpr));

        $this->walkTermsAndFactors($termsAndFactors, $searchParams);
    }

    private function walkTermsAndFactors(array $termsAndFactors, $searchParams) {
        foreach ($termsAndFactors['factors'] as $factor) {
            $this->walkANDFactor($factor, $searchParams);
        }

        foreach ($termsAndFactors['terms'] as $term) {
            $this->walkORTerm($term, $searchParams);
        }
    }

    private function walkORTerm($term, SearchParams $parentSearchParams, $statement = false) {
        if ($statement && !in_array($statement, ['must', 'should', 'must_not', 'should_not'])) {
            throw new InvalidParamsException(sprintf(
                "Parameter \$boolOptionInsert must be 'must' or 'should', got '%s'", $statement
            ));
        }

        $childSearchParams = clone $parentSearchParams;
        $parentBody = $parentSearchParams->getBody();
        $childSearchParams->setBody([]);

        if (is_array($term) && !empty($term)) {
            $this->walkTermsAndFactors($term, $childSearchParams);
        } else if ($term instanceof Node) {
            $this->walkConditionalPrimary($term, $childSearchParams);
        }

        if (!empty($childSearchParams->getBody())) {
            $this->walkerHelper->addSubQueryStatement($childSearchParams->getBody(), $parentBody, 'should');
            $parentSearchParams->setBody($parentBody);
        }
    }

    private function walkANDFactor($factor, SearchParams $parentSearchParams, $statement = false) {
        if ($statement && !in_array($statement, ['must', 'should', 'must_not', 'should_not'])) {
            throw new InvalidParamsException(sprintf(
                "Parameter \$boolOptionInsert must be 'must' or 'should', got '%s'", $statement
            ));
        }

        $childSearchParams = clone $parentSearchParams;
        $parentBody = $parentSearchParams->getBody();
        $childSearchParams->setBody([]);

        if (is_array($factor) && !empty($factor)) {
            $this->walkTermsAndFactors($factor, $childSearchParams);
        } else if ($factor instanceof Node) {
            $this->walkConditionalPrimary($factor, $childSearchParams);
        }

        if (!empty($childSearchParams->getBody())) {
            $this->walkerHelper->addSubQueryStatement($childSearchParams->getBody(), $parentBody, 'must');
            $parentSearchParams->setBody($parentBody);
        }
    }

    private function walkConditionalPrimary(Node $node, SearchParams $searchParams) {
        switch (get_class($node)) {
            case ComparisonExpression::class:
                /** @var ComparisonExpression $node */
                $this->walkComparisonExpression($node, $searchParams);
                break;
            case LikeExpression::class:
                /** @var LikeExpression $node */
                $this->walkLikeExpression($node, $searchParams);
                break;
            case BetweenExpression::class:
                /** @var BetweenExpression $node */
                $this->walkBetweenExpression($node, $searchParams);
                break;
            case NullComparisonExpression::class:
                /** @var NullComparisonExpression $node */
                $this->walkNullComparissionExpression($node, $searchParams);
                break;
            case InExpression::class:
                /** @var InExpression $node */
                $this->walkInExpression($node, $searchParams);
                break;
            default:
                throw new InvalidOperatorException(sprintf('%s operation not allowed', get_class($node)));
        }
    }

    private function walkInExpression(InExpression $inExpression, SearchParams $searchParams) {
        if (!$inExpression->expression->isSimpleArithmeticExpression()) {
            throw new QueryException("'IN' Expression is not allowed if not a simple IN query. ");
        }

        $pathExpr = $inExpression->expression->simpleArithmeticExpression;

        if ($pathExpr instanceof PathExpression) {
            $childSearchParams = clone $searchParams;
            $childSearchParams->setBody([]);
            $parentBody = $searchParams->getBody();
            $operator = $inExpression->not ? OperatorsMap::NEQ : OperatorsMap::EQ;

            /** @var Literal $literal */
            foreach ($inExpression->literals as $literal) {
                $conditional = new ComparisonExpression($pathExpr, $operator, $literal);

                $this->walkComparisonExpression($conditional, $childSearchParams);
            }

            $boolOp = $inExpression->not ? 'must' : 'should';
            $this->walkerHelper->addSubQueryStatement($childSearchParams->getBody(), $parentBody, $boolOp);
            $searchParams->setBody($parentBody);
        } else {
            throw new QueryException("'IN' expression must to point to a valid field path expression. ");
        }
    }

    private function walkComparisonExpression(ComparisonExpression $compExpr, SearchParams $searchParams) {
        /** @var ArithmeticExpression $leftExpr */
        $leftExpr = $compExpr->leftExpression;
        /** @var ArithmeticExpression $rightExpr */
        $rightExpr = $compExpr->rightExpression;

        $operator = $compExpr->operator;

        /** @var PathExpression $pathExpr */
        if ($leftExpr instanceof PathExpression) {
            $pathExpr = $leftExpr;
        } else {
            $pathExpr = $leftExpr->simpleArithmeticExpression;
        }

        /** @var Literal|PathExpression $valueExpr */
        if ($rightExpr instanceof Literal) {
            $valueExpr = $rightExpr;
        } else {
            $valueExpr = $rightExpr->simpleArithmeticExpression;
        }

        $field = $this->getElasticFieldNameOrThrowError($pathExpr->field);

        if ($valueExpr instanceof Literal) {
            $value = $valueExpr->value;
        } else if ($valueExpr instanceof PathExpression) {
            $value = $valueExpr->identificationVariable;
        } else {
            throw new \Doctrine\ORM\Query\QueryException("Right side expression for '$field' is not suported. ");
        }

        $this->addBodyStatement($field, $operator, $value, $searchParams);
    }

    private function walkLikeExpression(LikeExpression $likeExpr, SearchParams $searchParams) {
        $operator = $likeExpr->not ? OperatorsMap::UNLIKE : OperatorsMap::LIKE;

        /** @var PathExpression $stringExpr */
        $stringExpr = $likeExpr->stringExpression;
        /** @var Literal $stringPattern */
        $stringPattern = $likeExpr->stringPattern;

        $field = $this->getElasticFieldNameOrThrowError($stringExpr->field);
        $value = $stringPattern->value;

        $this->addBodyStatement($field, $operator, $value, $searchParams);
    }

    private function walkBetweenExpression(BetweenExpression $betweenExpr, SearchParams $searchParams) {
        $leftRangeExpr = $betweenExpr->leftBetweenExpression;
        $rightRangeExpr = $betweenExpr->rightBetweenExpression;
        $fieldExpr = $betweenExpr->expression;

        if ($leftRangeExpr->isSimpleArithmeticExpression()
            && $rightRangeExpr->isSimpleArithmeticExpression()
            && $fieldExpr->isSimpleArithmeticExpression()
        ) {
            /** @var Literal $lSimpleArithExpr */
            $lSimpleArithExpr = $leftRangeExpr->simpleArithmeticExpression;
            /** @var Literal $rSimpleArithExpr */
            $rSimpleArithExpr = $rightRangeExpr->simpleArithmeticExpression;
            /** @var PathExpression $fieldArithExpr */
            $fieldArithExpr = $fieldExpr->simpleArithmeticExpression;

            $value1 = $lSimpleArithExpr->value;
            $value2 = $rSimpleArithExpr->value;

            $field = $this->getElasticFieldNameOrThrowError($fieldArithExpr->field);

            $this->addBodyStatement($field, OperatorsMap::GTE, $value1, $searchParams);
            $this->addBodyStatement($field, OperatorsMap::LTE, $value2, $searchParams);
        } else {
            throw new InvalidOperatorException(sprintf('Between operation with not simple expression is not allowed. '));
        }
    }

    private function walkNullComparissionExpression(NullComparisonExpression $nullComExpr, SearchParams $searchParams) {
        /** @var PathExpression $expr */
        $expr = $nullComExpr->expression;

        $field = $this->getElasticFieldNameOrThrowError($expr->field);
        $operator = $nullComExpr->not ? OperatorsMap::NEQ : OperatorsMap::EQ;

        $this->addBodyStatement($field, $operator, null, $searchParams);
    }

    private function addBodyStatement($field, $operator, $value, SearchParams $searchParams) {
        $body = $searchParams->getBody();
        $this->walkerHelper->addBodyStatement($field, $operator, $value, $body);
        $searchParams->setBody($body);
    }

    private function getElasticFieldNameOrThrowError($columnName) {
        if (strstr($columnName, '.')) {
            $fieldLayers = explode('.', $columnName);
            $ESfield = $this->getFieldOrThrowError($fieldLayers[0]);
            $field = "{$ESfield->name}." . implode('.', array_slice($fieldLayers, 1));
        } else {
            $ESfield = $this->getFieldOrThrowError($columnName);
            $field = $ESfield->name;
        }

        return $field;
    }

    /**
     * @param string $columnName
     * @return Field
     * @throws QueryException
     */
    private function getFieldOrThrowError($columnName) {
        if (!isset($this->fieldAnnotations[$columnName])) {
            throw new QueryException(sprintf(
                "Unrecognized field '%s' in %s entity class. Does this column exist or have a Field Annotation?",
                $columnName, $this->className
            ));
        }

        return $this->fieldAnnotations[$columnName];
    }
}