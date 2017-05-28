<?php

namespace DoctrineElastic;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\ResultSetMapping;
use DoctrineElastic\Connection\ElasticConnection;
use DoctrineElastic\Elastic\DoctrineElasticEvents;
use DoctrineElastic\Elastic\ElasticQuery;
use DoctrineElastic\Elastic\ElasticQueryBuilder;
use DoctrineElastic\Listener\DeleteListener;
use DoctrineElastic\Listener\InsertListener;
use DoctrineElastic\Listener\QueryListener;
use DoctrineElastic\Listener\UpdateListener;
use DoctrineElastic\Repository\ElasticRepositoryManager;
use Elasticsearch\Client;

/**
 * ElasticEntityManager - leading class for all
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class ElasticEntityManager implements EntityManagerInterface {

    /** @var ElasticRepositoryManager */
    protected $repositoryManager;

    /** @var Configuration */
    protected $config;

    /** @var EventManager */
    protected $eventManager;

    /** @var ElasticUnitOfWork */
    protected $unitOfWork;

    /** @var Expr */
    protected $expressionBuilder;

    /** @var ElasticConnection */
    protected $conn;

    public function __construct(ElasticConnection $connection, EventManager $eventManager = null) {
        $this->eventManager = $eventManager;
        $this->conn = $connection;

        $this->repositoryManager = new ElasticRepositoryManager();
        $this->unitOfWork = new ElasticUnitOfWork($this);
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
        return $this->repositoryManager->getRepository($this, $className);
    }

    public function getReference($entityName, $id) {
        if (!is_array($id)) {
            $criteria = ['_id' => $id];
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
        trigger_error(__METHOD__ . ' method is not supported. ');
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
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function transactional($func) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function commit() {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function rollback() {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function createQuery($dql = '') {
        $query = new ElasticQuery($this);

        if (!empty($dql)) {
            $query->setDQL($dql);
        }

        return $query;
    }

    public function createNamedQuery($name) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function createNativeQuery($sql, ResultSetMapping $rsm) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function createNamedNativeQuery($name) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function createQueryBuilder() {
        return new ElasticQueryBuilder($this);
    }

    public function getPartialReference($entityName, $identifier) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function close() {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function copy($entity, $deep = false) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function lock($entity, $lockMode, $lockVersion = null) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function getEventManager() {
        if (is_null($this->eventManager)) {
            $this->eventManager = new EventManager();
        }

        return $this->eventManager;
    }

    public function getConfiguration() {
        return [];
    }

    public function isOpen() {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function getHydrator($hydrationMode) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function newHydrator($hydrationMode = null) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function getProxyFactory() {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function getFilters() {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function isFiltersStateClean() {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function hasFilters() {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function persist($object) {
        return $this->unitOfWork->persist($object);
    }

    public function remove($object) {
        return $this->unitOfWork->delete($object);
    }

    public function merge($object) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function clear($objectName = null) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function detach($object) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function refresh($object) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function flush($entity = null) {
        $this->getUnitOfWork()->commit($entity);
    }

    public function getMetadataFactory() {
        trigger_error(__METHOD__ . ' method not supported. ');
    }

    public function initializeObject($obj) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function contains($object) {
        trigger_error(__METHOD__ . ' method is not supported. ');
    }

    public function getClassMetadata($className) {
        trigger_error(__METHOD__ . ' method not supported. ');
    }
}