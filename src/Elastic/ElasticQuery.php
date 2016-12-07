<?php

namespace DoctrineElastic\Elastic;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use DoctrineElastic\Decorators\ElasticEntityManager;
use DoctrineElastic\Event\QueryEventArgs;
use DoctrineElastic\Query\ElasticParser;
use DoctrineElastic\Query\ElasticParserResult;
use DoctrineElastic\Service\ElasticSearchService;

class ElasticQuery {

    /** @var ElasticEntityManager */
    protected $entityManager;

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

    public function __construct(ElasticEntityManager $entityManager) {
        $this->entityManager = $entityManager;
        $this->searchService = new ElasticSearchService($entityManager->getConnection());
    }

    public function getResult() {
        $parser = new ElasticParser($this);
        $parserResult = $parser->parseElasticQuery();

        $paramMappings = $parserResult->getParameterMappings();
        $paramCount = count($this->_parameters);
        $mappingCount = count($paramMappings);

        if ($paramCount > $mappingCount) {
            throw QueryException::tooManyParameters($mappingCount, $paramCount);
        } elseif ($paramCount < $mappingCount) {
            throw QueryException::tooFewParameters($mappingCount, $paramCount);
        }

        $eventArgs = new QueryEventArgs($this);
        $eventArgs->setAST($parser->getAST());
        $this->getEntityManager()->getEventManager()->dispatchEvent(DoctrineElasticEvents::beforeQuery, $eventArgs);

        $results = $parserResult->getElasticExecutor()->execute($parserResult->getSearchParams());

        $eventArgs->setResults($results);
        $this->getEntityManager()->getEventManager()->dispatchEvent(DoctrineElasticEvents::postQuery, $eventArgs);

        return $results;
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