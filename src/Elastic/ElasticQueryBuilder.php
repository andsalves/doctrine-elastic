<?php

namespace DoctrineElastic\Elastic;

use Doctrine\ORM\QueryBuilder;
use DoctrineElastic\Exception\ElasticOperationException;

/**
 * DoctrineElastic QueryBuilder representation
 *
 * @author Ands
 */
class ElasticQueryBuilder extends QueryBuilder {

    /**
     * @return ElasticQuery
     */
    public function getQuery() {
        $parameters = $this->getParameters();
        $parameters = clone $parameters;

        $query = $this->getEntityManager()->createQuery($this->getDQL())
            ->setParameters($parameters)
            ->setFirstResult($this->getFirstResult())
            ->setMaxResults($this->getMaxResults());

        return $query;
    }

    public function select($select = null) {
        if (strstr($select, '.')) {
            throw new ElasticOperationException('Not supported operation: Select specific fields');
        }

        return parent::select($select);
    }

    public function innerJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null) {
        throw new ElasticOperationException('Not supported operation: ' . __FUNCTION__);
    }

    public function groupBy($groupBy) {
        throw new ElasticOperationException('Not supported operation: ' . __FUNCTION__);
    }

    public function addGroupBy($groupBy) {
        throw new ElasticOperationException('Not supported operation: ' . __FUNCTION__);
    }

    public function having($having) {
        throw new ElasticOperationException('Not supported operation: ' . __FUNCTION__);
    }

    public function addSelect($select = null) {
        throw new ElasticOperationException('Not supported operation: ' . __FUNCTION__);
    }

    public function andHaving($having) {
        throw new ElasticOperationException('Not supported operation: ' . __FUNCTION__);
    }

    public function join($join, $alias, $conditionType = null, $condition = null, $indexBy = null) {
        throw new ElasticOperationException('Not supported operation: ' . __FUNCTION__);
    }

    public function distinct($flag = true) {
        throw new ElasticOperationException('Not supported operation: ' . __FUNCTION__);
    }

    public function leftJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null) {
        throw new ElasticOperationException('Not supported operation: ' . __FUNCTION__);
    }
}