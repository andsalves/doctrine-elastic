<?php

namespace DoctrineElastic\Decorators;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Elasticsearch\Client;

abstract class EntityPersisterDecorator implements EntityPersister {

    /** @var EntityPersister */
    protected $wrapped;

    /** @var \Doctrine\ORM\Mapping\QuoteStrategy */
    protected $quoteStrategy;


    /** @var Client */
    protected $elastic;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var ClassMetadata */
    protected $class;

    public function __construct(EntityManagerInterface $em, ClassMetadata $class, Client $elastic) {
        $entityPersister = new BasicEntityPersister($em, $class);
        $this->wrapped = $entityPersister;
        $this->elastic = $elastic;
        $this->class = $class;
        $this->em = $em;
        $this->quoteStrategy = $this->em->getConfiguration()->getQuoteStrategy();
    }

    public function getResultSetMapping() {
        return $this->wrapped->getResultSetMapping();
    }

    public function getInserts() {
        return $this->wrapped->getInserts();
    }

    public function getInsertSQL() {
        return $this->wrapped->getInsertSQL();
    }

    public function getSelectSQL($criteria, $assoc = null, $lockMode = null, $limit = null, $offset = null, array $orderBy = null) {
        return $this->wrapped->getSelectSQL($criteria, $assoc, $lockMode, $limit, $offset, $orderBy);
    }

    public function getCountSQL($criteria = array()) {
        return $this->wrapped->getCountSQL($criteria);
    }

    public function expandParameters($criteria) {
        return $this->wrapped->expandParameters($criteria);
    }

    public function expandCriteriaParameters(Criteria $criteria) {
        return $this->wrapped->expandCriteriaParameters($criteria);
    }

    public function getSelectConditionStatementSQL($field, $value, $assoc = null, $comparison = null) {
        return $this->wrapped->getSelectConditionStatementSQL($field, $value, $assoc, $comparison);
    }

    public function addInsert($entity) {
        return $this->wrapped->addInsert($entity);
    }

    public function executeInserts() {
        return $this->wrapped->executeInserts();
    }

    public function update($entity) {
        return $this->wrapped->update($entity);
    }

    public function delete($entity) {
        return $this->wrapped->delete($entity);
    }

    public function count($criteria = array()) {
        return $this->wrapped->count($criteria);
    }

    public function getOwningTable($fieldName) {
        return $this->wrapped->getOwningTable($fieldName);
    }

    public function load(array $criteria, $entity = null, $assoc = null, array $hints = array(), $lockMode = null, $limit = null, array $orderBy = null) {
        return $this->wrapped->load($criteria, $entity, $assoc, $hints, $lockMode, $limit, $orderBy);
    }

    public function loadById(array $identifier, $entity = null) {
        return $this->wrapped->loadById($identifier, $entity);
    }

    public function loadOneToOneEntity(array $assoc, $sourceEntity, array $identifier = array()) {
        return $this->wrapped->loadOneToOneEntity($assoc, $sourceEntity, $identifier);
    }

    public function loadCriteria(Criteria $criteria) {
        return $this->wrapped->loadCriteria($criteria);
    }

    public function loadAll(array $criteria = array(), array $orderBy = null, $limit = null, $offset = null) {
        return $this->wrapped->loadAll($criteria, $orderBy, $limit, $offset);
    }

    public function getManyToManyCollection(array $assoc, $sourceEntity, $offset = null, $limit = null) {
        return $this->wrapped->getManyToManyCollection($assoc, $sourceEntity, $offset, $limit);
    }

    public function loadManyToManyCollection(array $assoc, $sourceEntity, PersistentCollection $collection) {
        return $this->wrapped->loadManyToManyCollection($assoc, $sourceEntity, $collection);
    }

    public function loadOneToManyCollection(array $assoc, $sourceEntity, PersistentCollection $collection) {
        return $this->wrapped->loadOneToManyCollection($assoc, $sourceEntity, $collection);
    }

    public function lock(array $criteria, $lockMode) {
        $this->wrapped->lock($criteria, $lockMode);
    }

    public function getOneToManyCollection(array $assoc, $sourceEntity, $offset = null, $limit = null) {
        return $this->wrapped->getOneToManyCollection($assoc, $sourceEntity, $offset, $limit);
    }

    public function exists($entity, Criteria $extraConditions = null) {
        return $this->wrapped->exists($entity, $extraConditions);
    }

    public function getClassMetadata() {
        return $this->wrapped->getClassMetadata();
    }

    public function refresh(array $id, $entity, $lockMode = null) {
        $this->wrapped->refresh($id, $entity, $lockMode);
    }
}