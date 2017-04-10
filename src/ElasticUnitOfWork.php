<?php

namespace DoctrineElastic;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\ORMInvalidArgumentException;
use DoctrineElastic\Hydrate\SimpleEntityHydrator;
use DoctrineElastic\Persister\ElasticEntityPersister;
use Elasticsearch\Client;
use InvalidArgumentException;

/**
 * Here is a Elastic adaptation for UoW of Doctrine.
 * There is many simplifications, just like entity states, persist, remove, delete, commit actions,
 * and much more.
 *
 * @author Ands
 */
class ElasticUnitOfWork {

    /**
     * Entity has been persisted and now is managed by EntityManager
     */
    const STATE_MANAGED = 1;

    /**
     * Entity has been instantiated, and is not managed by EntityManager (yet)
     */
    const STATE_NEW = 2;

    /**
     * Entity has value(s) for identity field(s) and exists in elastic, therefore,
     * is not managed by EntityManager (yet)
     */
    const STATE_DETACHED = 3;

    /**
     * Entity was deleted by remove method, and will be removed on commit
     */
    const STATE_DELETED = 4;

    /* @var ElasticEntityManager $em */
    private $em;

    /* @var EntityManagerInterface $em */
    private $elastic;

    /** @var ClassMetadataInfo[] */
    private $commitOrder = [];

    protected $entityDeletions;
    protected $entityInsertions;
    protected $entityUpdates;

    private $hydrator;

    public function __construct(EntityManagerInterface $em, Client $elastic) {
        $this->em = $em;
        $this->elastic = $elastic;
        $this->hydrator = new SimpleEntityHydrator();
    }

    /**
     * @param string $entityName
     * @return ElasticEntityPersister
     */
    public function getEntityPersister($entityName) {
        $class = $this->em->getClassMetadata($entityName);

        return new ElasticEntityPersister($this->em, $class, $this->elastic);
    }

    /**
     * @param object $entity
     */
    public function persist($entity) {
        $oid = spl_object_hash($entity);

        switch ($this->getEntityState($entity)) {
            default:
                if (isset($this->entityInsertions[$oid])) {
                    unset($this->entityInsertions[$oid]);
                }
                if (isset($this->entityUpdates[$oid])) {
                    unset($this->entityUpdates[$oid]);
                }

                $this->scheduleForInsert($entity);
                break;
            case self::STATE_DETACHED:
                $this->scheduleForUpdate($entity);
                break;
        }

        $this->commitOrder[] = $this->em->getClassMetadata(get_class($entity));
    }

    /**
     * @param object $entity
     * @return int
     */
    public function getEntityState($entity) {
        if ($this->isEntityScheduled($entity)) {
            if ($this->isScheduledForDelete($entity)) {
                return self::STATE_DELETED;
            } else {
                return self::STATE_MANAGED;
            }
        }

        $persister = $this->getEntityPersister(get_class($entity));

        if (method_exists($entity, 'get_id')) {
            $_id = $entity->get_id();
        } else {
            $_id = $entity->_id;
        }

        if (boolval($_id)) {
            $element = $persister->loadById(['_id' => $_id]);

            if ($element) {
                return self::STATE_DETACHED;
            }
        }

        return self::STATE_NEW;
    }

    /**
     * @param object $entity
     */
    public function scheduleForInsert($entity) {
        if ($this->isScheduledForUpdate($entity)) {
            throw new InvalidArgumentException('Dirty entity can not be scheduled for insertion.');
        }

        if ($this->isScheduledForDelete($entity)) {
            throw ORMInvalidArgumentException::scheduleInsertForRemovedEntity($entity);
        }

        if ($this->isScheduledForInsert($entity)) {
            throw ORMInvalidArgumentException::scheduleInsertTwice($entity);
        }

        $this->entityInsertions[spl_object_hash($entity)] = $entity;
    }

    public function isScheduledForInsert($entity) {
        return isset($this->entityInsertions[spl_object_hash($entity)]);
    }

    public function isScheduledForUpdate($entity) {
        return isset($this->entityUpdates[spl_object_hash($entity)]);
    }

    public function isScheduledForDelete($entity) {
        return isset($this->entityDeletions[spl_object_hash($entity)]);
    }

    public function isEntityScheduled($entity) {
        $oid = spl_object_hash($entity);

        return isset($this->entityInsertions[$oid])
        || isset($this->entityUpdates[$oid])
        || isset($this->entityDeletions[$oid]);
    }

    public function scheduleForUpdate($entity) {
        $oid = spl_object_hash($entity);

        if (isset($this->entityDeletions[$oid])) {
            throw ORMInvalidArgumentException::entityIsRemoved($entity, "schedule for update");
        }

        if (!isset($this->entityUpdates[$oid]) && !isset($this->entityInsertions[$oid])) {
            $this->entityUpdates[$oid] = $entity;
        }
    }

    public function scheduleForDelete($entity) {
        $oid = spl_object_hash($entity);

        if (isset($this->entityInsertions[$oid])) {
            unset($this->entityInsertions[$oid]);
        }

        if (isset($this->entityUpdates[$oid])) {
            unset($this->entityUpdates[$oid]);
        }

        if (!isset($this->entityDeletions[$oid])) {
            $this->entityDeletions[$oid] = $entity;
        }
    }

    /**
     * @param null|object|array $entity
     * @return void
     * @throws \Exception
     */
    public function commit($entity = null) {
        if ($this->em->getEventManager()->hasListeners(Events::preFlush)) {
            $this->em->getEventManager()->dispatchEvent(Events::preFlush, new PreFlushEventArgs($this->em));
        }

        $this->dispatchOnFlushEvent();
        $commitOrder = $this->getCommitOrder();

//        $conn = $this->em->getConnection();
//        $conn->beginTransaction();

        try {
            if ($this->entityInsertions) {
                foreach ($commitOrder as $classMetadata) {
                    $this->executeInserts($classMetadata);
                }
            }

            if ($this->entityUpdates) {
                foreach ($commitOrder as $classMetadata) {
                    $this->executeUpdates($classMetadata);
                }
            }

            if ($this->entityDeletions) {
                for ($count = count($commitOrder), $i = $count - 1; $i >= 0 && $this->entityDeletions; --$i) {
                    $this->executeDeletions($commitOrder[$i]);
                }
            }

//            $conn->commit();
        } catch (\Exception $e) {
            $this->afterTransactionRolledBack();
            throw $e;
        }

        $this->afterTransactionComplete();
        $this->dispatchPostFlushEvent();
        $this->clear($entity);
    }

    public function executeInserts(ClassMetadataInfo $classMetadata) {
        $persister = $this->getEntityPersister($classMetadata->name);

        foreach ($this->entityInsertions as $oid => $entity) {
            if ($this->em->getClassMetadata(get_class($entity))->name !== $classMetadata->name) {
                continue;
            }

            $persister->addInsert($entity);
            unset($this->entityInsertions[$oid]);
        }

        $persister->executeInserts();
    }

    public function executeUpdates(ClassMetadataInfo $classMetadata) {
        $persister = $this->getEntityPersister($classMetadata->name);

        foreach ($this->entityUpdates as $oid => $entity) {
            if ($this->em->getClassMetadata(get_class($entity))->name !== $classMetadata->name) {
                continue;
            }

            $persister->update($entity);
            unset($this->entityUpdates[$oid]);
        }
    }

    public function executeDeletions(ClassMetadataInfo $classMetadata) {
        $persister = $this->getEntityPersister($classMetadata->name);

        foreach ($this->entityDeletions as $oid => $entity) {
            if ($this->em->getClassMetadata(get_class($entity))->name !== $classMetadata->name) {
                continue;
            }

            $persister->delete($entity);
            unset($this->entityDeletions[$oid]);
        }
    }

    protected function dispatchOnFlushEvent() {

    }

    protected function dispatchPostFlushEvent() {

    }

    public function clear($entity = null) {
        if ($entity === null) {
            $this->entityInsertions =
            $this->entityUpdates =
            $this->entityDeletions =
            $this->commitOrder = [];
        } else {
            $this->clearEntityInsertions($entity);
            $this->clearEntityUpdate($entity);
            $this->clearEntityDeletions($entity);
        }

        if ($this->em->getEventManager()->hasListeners(Events::onClear)) {
            $this->em->getEventManager()->dispatchEvent(
                Events::onClear, new OnClearEventArgs($this->em, get_class($entity))
            );
        }
    }

    private function clearEntityInsertions($entity = null) {
        if ($entity === null) {
            $this->entityInsertions = [];
        } else {
            $oid = spl_object_hash($entity);
            if (isset($this->entityInsertions[$oid])) {
                unset($this->entityInsertions[$oid]);
            }
        }

    }

    private function clearEntityUpdate($entity = null) {
        if ($entity === null) {
            $this->entityUpdates = [];
        } else {
            $oid = spl_object_hash($entity);
            if (isset($this->entityUpdates[$oid])) {
                unset($this->entityUpdates[$oid]);
            }
        }

    }

    public function delete($entity) {
        if(!is_object($entity)){
            throw new InvalidArgumentException('Trying to schedule a non object to delete');
        }

        $this->scheduleForDelete($entity);
        $this->commitOrder[] = $this->em->getClassMetadata(get_class($entity));
    }

    private function clearEntityDeletions($entity = null) {
        if ($entity === null) {
            $this->entityDeletions = [];
        } else {
            $oid = spl_object_hash($entity);
            if (isset($this->entityDeletions[$oid])) {
                unset($this->entityDeletions[$oid]);
            }
        }

    }

    /**
     * @return ClassMetadataInfo[]
     */
    public function getCommitOrder() {
        return $this->commitOrder;
    }

    protected function afterTransactionRolledBack() {

    }

    protected function afterTransactionComplete() {

    }

    public function createEntity() {

    }
}