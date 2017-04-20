<?php

namespace DoctrineElastic\Query;

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
use DoctrineElastic\Service\ElasticSearchService;

/**
 * Main walker for queries
 *
 * @uahtor Ands
 */
class ElasticWalker {

    /** @var ElasticQuery */
    protected $query;

    /** @var string */
    private $_className;

    /** @var WalkerHelper */
    private $walkerHelper;

    /** @var SelectStatement */
    private $_ast;

    public function __construct(ElasticQuery $query, SelectStatement $AST, $className) {
        $this->query = $query;
        $this->walkerHelper = new WalkerHelper();
        $this->_ast = $AST;
        $this->_className = $className;
    }

    /**
     * @return SearchParams
     */
    public function walkSelectStatement() {
        $searchParams = new SearchParams();
        $size = $this->query->getMaxResults();
        $offset = $this->query->getFirstResult();

        $type = $this->getEntityElasticType($this->_className);

        $searchParams->setType($type->getName());
        $searchParams->setIndex($type->getIndex());

        $this->walkSelectClause($this->_ast->selectClause, $searchParams);
        $this->walkFromClause($this->_ast->fromClause, $searchParams);

        if ($this->_ast->whereClause) {
            $this->walkWhereClause($this->_ast->whereClause, $searchParams);
        }

        if ($this->_ast->groupByClause) {
            $this->walkGroupByClause($this->_ast->groupByClause, $searchParams);
        }

        if ($this->_ast->havingClause) {
            $this->walkHavingClause($this->_ast->havingClause, $searchParams);
        }

        if ($this->_ast->orderByClause) {
            $this->walkOrderByClause($this->_ast->orderByClause, $searchParams);
        }

        $searchParams->setSize($size);
        $searchParams->setFrom($offset);

        return $searchParams;
    }

    private function walkSelectClause(SelectClause $selectClause, SearchParams $searchParams) {

    }

    private function walkFromClause(FromClause $fromClause, SearchParams $searchParams) {

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
            $propertyName = $item->expression->field;
            $ESField = $this->walkerHelper->getEntityElasticField($propertyName, $this->_className, $this->query);

            $searchParams->setSort([$ESField->name => strtolower($order)]);
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
}