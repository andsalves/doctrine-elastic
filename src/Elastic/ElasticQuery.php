<?php

namespace DoctrineElastic\Elastic;

use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Query\QueryParser;
use DoctrineElastic\Query\ElasticQueryExecutor;

/**
 * DoctrineElastic Query representation
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class ElasticQuery {

    /** @var ElasticEntityManager */
    protected $entityManager;

    /** @var ElasticQueryExecutor */
    protected $queryExecutor;

    /** @var int */
    protected $_firstResult;

    /** @var int */
    protected $_maxResults;

    /** @var string */
    protected $_dql;

    /** @var array */
    protected $_parameters;

    public function __construct(ElasticEntityManager $entityManager) {
        $this->entityManager = $entityManager;
        $this->queryExecutor = new ElasticQueryExecutor($entityManager);
    }

    public function getResult() {
        $parser = new QueryParser($this);
        $searchParams = $parser->parseElasticQuery();

        return $this->queryExecutor->execute($searchParams, $parser->getRootClass());
    }

    public function getDQL() {
        return $this->_dql;
    }

    public function setDQL($dql) {
        $this->_dql = $dql;
    }

    protected function processParameterMappings($paramMappings) {

    }

    /**
     * @return mixed
     */
    public function getParameters() {
        return $this->_parameters;
    }

    /**
     * @param mixed $parameters
     * @return ElasticQuery
     */
    public function setParameters($parameters) {
        $this->_parameters = $parameters;
        return $this;
    }

    public function setFirstResult($offset) {
        $this->_firstResult = $offset;
        return $this;
    }

    public function setMaxResults($limit) {
        $this->_maxResults = $limit;
        return $this;
    }

    public function getFirstResult() {
        return $this->_firstResult;
    }

    public function getMaxResults() {
        return $this->_maxResults;
    }

    public function getEntityManager() {
        return $this->entityManager;
    }

    public function getOneOrNullResult() {
        $results = $this->getResult();

        return count($results) ? reset($results) : null;
    }

    public function getSingleResult() {
        return $this->getOneOrNullResult();
    }
}