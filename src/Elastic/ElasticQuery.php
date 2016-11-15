<?php

namespace DoctrineElastic\Elastic;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use DoctrineElastic\Decorators\ElasticEntityManager;
use DoctrineElastic\Query\ElasticParser;
use DoctrineElastic\Query\ElasticParserResult;
use DoctrineElastic\Service\ElasticSearchService;

class ElasticQuery {

    /** @var ElasticEntityManager */
    protected $entityManager;

    /** @var ElasticParserResult */
    private $parserResult;

    /** @var ElasticSearchService */
    protected $searchService;

    /** @var int */
    protected $_firstResult;

    /** @var int */
    protected $_maxResults;

    /** @var string */
    protected $_dql;

    /** @var array */
    protected $_parameters;

    public function __construct(EntityManagerInterface $entityManager, ElasticSearchService $searchService) {
        $this->entityManager = $entityManager;
        $this->searchService = $searchService;
    }

    public function getResult() {
        $parserResult = $this->parse();
        $executor = $parserResult->getElasticExecutor();

        // Prepare parameters
        $paramMappings = $this->parserResult->getParameterMappings();
        $paramCount = count($this->_parameters);
        $mappingCount = count($paramMappings);

        if ($paramCount > $mappingCount) {
            throw QueryException::tooManyParameters($mappingCount, $paramCount);
        } elseif ($paramCount < $mappingCount) {
            throw QueryException::tooFewParameters($mappingCount, $paramCount);
        }

        $data = $executor->execute($this->searchService, $this->entityManager);

        return $data;
    }

    private function parse() {
        $parser = new ElasticParser($this, $this->entityManager);

        $this->parserResult = $parser->parseElasticQuery();

        return $this->parserResult;
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