<?php

namespace DoctrineElastic\Decorators;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Elasticsearch\Client;

/**
 * @author Ands
 */
class ElasticUnitOfWork extends UnitOfWork {

    /* @var EntityManagerInterface $em */
    private $em;

    /* @var EntityManagerInterface $em */
    private $elastic;

    /* @var UnitOfWork $wrapped */
    private $wrapped;

    public function __construct(EntityManagerInterface $em, Client $elastic) {
        $this->em = $em;
        $this->wrapped = new UnitOfWork($this->em);
        $this->elastic = $elastic;
        parent::__construct($this->em);
    }

    /**
     * @param string $entityName
     * @return ElasticedEntityPersister
     */
    public function getEntityPersister($entityName) {
        $class = $this->em->getClassMetadata($entityName);

        return new ElasticedEntityPersister($this->em, $class, $this->elastic);
    }

    public function __call($name, $arguments) {
        return call_user_func_array([$this->wrapped, $name], $arguments);
    }
}