<?php

namespace DoctrineElastic\Query;

use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\OrderByItem;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use DoctrineElastic\Elastic\ElasticQuery;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Hydrate\AnnotationEntityHydrator;
use DoctrineElastic\Mapping\Field;
use DoctrineElastic\Query\Walker\Helper\WalkerHelper;
use DoctrineElastic\Query\Walker\WhereWalker;

/**
 * Main walker for queries
 *
 * @uahtor Ands
 */
class ElasticWalker
{
    /** @var ElasticQuery */
    protected $query;

    /** @var string */
    private $_className;

    /** @var WalkerHelper */
    private $walkerHelper;

    /** @var SelectStatement */
    private $_ast;

    public function __construct(ElasticQuery $query, SelectStatement $AST, $className)
    {
        $this->query = $query;
        $this->walkerHelper = new WalkerHelper();
        $this->_ast = $AST;
        $this->_className = $className;
    }

    /**
     * @return SearchParams
     */
    public function walkSelectStatement()
    {
        $searchParams = new SearchParams();
        $size = $this->query->getMaxResults();
        $offset = $this->query->getFirstResult();

        $persister = $this->query->getEntityManager()->getUnitOfWork()->getEntityPersister($this->_className);
        $type = $persister->getEntityType();

        $searchParams->setType($type->getName());
        $searchParams->setIndex($type->getIndex());

        if ($this->_ast->whereClause) {
            $this->walkWhereClause($this->_ast->whereClause, $searchParams);
        }

        if ($this->_ast->orderByClause) {
            $this->walkOrderByClause($this->_ast->orderByClause, $searchParams);
        }

        $searchParams->setSize($size);
        $searchParams->setFrom($offset);

        return $searchParams;
    }

    private function walkWhereClause(WhereClause $whereClause, SearchParams $searchParams)
    {
        $whereWalker = new WhereWalker($this->query, $this->_className, $this->walkerHelper);
        $whereWalker->walk($whereClause, $searchParams);
    }

    private function walkOrderByClause(OrderByClause $orderByClause, SearchParams $searchParams)
    {
        $sort = [];

        /** @var OrderByItem $item */
        foreach ($orderByClause->orderByItems as $item) {
            $order = $item->type;
            $propertyName = $item->expression->field;
            $ESField = $this->getEntityElasticField($propertyName, $this->_className);

            if (is_null($ESField)) {
                continue;
            }

            $sort[$ESField->name] = strtolower($order);
        }

        if (!empty($sort)) {
            $searchParams->setSort($sort);
        }
    }

    /**
     * @param $propertyName
     * @param $className
     * @return Field
     */
    private function getEntityElasticField($propertyName, $className)
    {
        /** @var Field[] $fields */
        $fields = (new AnnotationEntityHydrator())->extractSpecAnnotations($className, Field::class);

        return isset($fields[$propertyName]) ? $fields[$propertyName] : null;
    }
}
