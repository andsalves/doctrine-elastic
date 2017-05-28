<?php

namespace DoctrineElastic\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use DoctrineElastic\Elastic\ElasticQueryBuilder;
use DoctrineElastic\ElasticEntityManager;

/**
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class ElasticEntityRepository implements ObjectRepository {

    /** @var string */
    protected $_entityName;

    /** @var ElasticEntityManager */
    protected $_em;

    public function __construct(ElasticEntityManager $em, $className) {
        $this->_entityName = $className;
        $this->_em = $em;
    }

    /**
     * Finds an object by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     *
     * @return object The object.
     */
    public function find($id) {
        return $this->_em->find($this->_entityName, $id, null, null);
    }

    /**
     * Finds all objects in the repository.
     *
     * @return array The objects.
     */
    public function findAll() {
        return $this->findBy([]);
    }

    /**
     * Finds objects by a set of criteria.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an UnexpectedValueException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return array The objects.
     *
     * @throws \UnexpectedValueException
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null) {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);

        return $persister->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Finds a single object by a set of criteria.
     *
     * @param array $criteria The criteria.
     * @param array $orderBy
     * @return object The object.
     */
    public function findOneBy(array $criteria, array $orderBy = null) {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);

        return $persister->load($criteria, 1, $orderBy);
    }

    /**
     * Returns the class name of the object managed by the repository.
     *
     * @return string
     */
    public function getClassName() {
        return $this->_entityName;
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return ElasticQueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null) {
        return $this->_em->createQueryBuilder()
            ->select($alias)
            ->from($this->_entityName, $alias, $indexBy);
    }
}