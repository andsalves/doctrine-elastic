<?php

namespace DoctrineElastic\Decorators;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use DoctrineElastic\Connection\ElasticConnection;
use DoctrineElastic\Elastic\DoctrineElasticEvents;
use DoctrineElastic\Elastic\ElasticQuery;
use DoctrineElastic\Elastic\ElasticQueryBuilder;
use DoctrineElastic\Listener\DeleteListener;
use DoctrineElastic\Listener\InsertListener;
use DoctrineElastic\Listener\QueryListener;
use DoctrineElastic\Listener\UpdateListener;
use DoctrineElastic\Mapping\ElasticClassMetadataFactory;
use DoctrineElastic\Service\ElasticSearchService;
use Elasticsearch\Client;

/**
 * @author Ands
 */
class ElasticEntityManager implements EntityManagerInterface {

    /** @var DefaultRepositoryFactory */
    protected $repositoryFactory;

    /** @var Configuration */
    protected $config;

    /** @var EventManager */
    protected $eventManager;

    /** @var ElasticUnitOfWork */
    protected $unitOfWork;

    /** @var Expr */
    protected $expressionBuilder;

    /** @var Client */
    private $elastic;

    /** @var ElasticSearchService */
    private $searchService;

    /** @var ElasticConnection */
    protected $conn;
    /**
     * @var ElasticClassMetadataFactory
     */
    protected $metadataFactory;

    public function __construct(Configuration $config, Client $elastic, EventManager $eventManager) {
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->conn = new ElasticConnection($elastic);

        $this->metadataFactory = new ElasticClassMetadataFactory($this);
        $this->metadataFactory->setCacheDriver($this->config->getMetadataCacheImpl());

        $this->repositoryFactory = new DefaultRepositoryFactory();
        $this->unitOfWork = new ElasticUnitOfWork($this, $elastic);
        $this->elastic = $elastic;
        $this->searchService = new ElasticSearchService($this->conn);
        $this->registerEventsListeners();
    }

    private function registerEventsListeners() {
        $this->getEventManager()->addEventListener(array(
            DoctrineElasticEvents::beforeInsert,
            DoctrineElasticEvents::postInsert,
        ), new InsertListener());

        $this->getEventManager()->addEventListener(array(
            DoctrineElasticEvents::beforeDelete,
            DoctrineElasticEvents::postDelete,
        ), new DeleteListener());

        $this->getEventManager()->addEventListener(array(
            DoctrineElasticEvents::beforeDelete,
            DoctrineElasticEvents::postDelete,
        ), new UpdateListener());

        $this->getEventManager()->addEventListener(array(
            DoctrineElasticEvents::beforeQuery,
            DoctrineElasticEvents::postQuery,
        ), new QueryListener());
    }

    /**
     * @return ElasticUnitOfWork
     */
    public function getUnitOfWork() {
        return $this->unitOfWork;
    }

    public function getRepository($className) {
        return $this->repositoryFactory->getRepository($this, $className);
    }

    public function getReference($entityName, $id) {
        $class = $this->getMetadataFactory()->getMetadataFor(ltrim($entityName, '\\'));

        if (!is_array($id)) {
            $criteria = array($class->getIdentifier()[0] => $id);
        } else {
            $criteria = $id;
        }

        $persister = $this->getUnitOfWork()->getEntityPersister($entityName);

        return $persister->load($criteria);
    }

    public function find($entityName, $id, $lockMode = null, $lockVersion = null) {
        return $this->getReference($entityName, $id);
    }

    public function getCache() {
        // TODO: Implement getCache() method.
    }

    /**
     * @return ElasticConnection
     */
    public function getConnection() {
        return $this->conn;
    }

    public function getExpressionBuilder() {
        if ($this->expressionBuilder === null) {
            $this->expressionBuilder = new Expr();
        }

        return $this->expressionBuilder;
    }

    public function beginTransaction() {
        // TODO: Implement beginTransaction() method.
    }

    public function transactional($func) {
        // TODO: Implement transactional() method.
    }

    public function commit() {
        // TODO: Implement commit() method.
    }

    public function rollback() {
        // TODO: Implement rollback() method.
    }

    public function createQuery($dql = '') {
        $query = new ElasticQuery($this);

        if (!empty($dql)) {
            $query->setDQL($dql);
        }

        return $query;
    }

    public function createNamedQuery($name) {
        // TODO: Implement createNamedQuery() method.
    }

    public function createNativeQuery($sql, ResultSetMapping $rsm) {
        // TODO: Implement createNativeQuery() method.
    }

    public function createNamedNativeQuery($name) {
        // TODO: Implement createNamedNativeQuery() method.
    }

    public function createQueryBuilder() {
        return new ElasticQueryBuilder($this);
    }

    public function getPartialReference($entityName, $identifier) {
        // TODO: Implement getPartialReference() method.
    }

    public function close() {
        // TODO: Implement close() method.
    }

    public function copy($entity, $deep = false) {
        // TODO: Implement copy() method.
    }

    public function lock($entity, $lockMode, $lockVersion = null) {
        // TODO: Implement lock() method.
    }

    public function getEventManager() {
        return $this->eventManager;
    }

    public function getConfiguration() {
        return $this->config;
    }

    public function isOpen() {
        // TODO: Implement isOpen() method.
    }

    public function getHydrator($hydrationMode) {
        // TODO: Implement getHydrator() method.
    }

    public function newHydrator($hydrationMode = null) {
        // TODO: Implement newHydrator() method.
    }

    public function getProxyFactory() {
        // TODO: Implement getProxyFactory() method.
    }

    public function getFilters() {
        // TODO: Implement getFilters() method.
    }

    public function isFiltersStateClean() {
        // TODO: Implement isFiltersStateClean() method.
    }

    public function hasFilters() {
        // TODO: Implement hasFilters() method.
    }

    public function persist($object) {
        return $this->unitOfWork->persist($object);
    }

    public function remove($object) {
        return $this->unitOfWork->delete($object);
    }

    public function merge($object) {
        // TODO: Implement merge() method.
    }

    public function clear($objectName = null) {
        // TODO: Implement clear() method.
    }

    public function detach($object) {
        // TODO: Implement detach() method.
    }

    public function refresh($object) {
        // TODO: Implement refresh() method.
    }

    public function flush($entity = null) {
        $this->getUnitOfWork()->commit($entity);
    }

    public function getMetadataFactory() {
        return $this->metadataFactory;
    }

    public function initializeObject($obj) {
        // TODO: Implement initializeObject() method.
    }

    public function contains($object) {
        // TODO: Implement contains() method.
    }

    public function getClassMetadata($className) {
        return $this->metadataFactory->getMetadataFor($className);
    }
}