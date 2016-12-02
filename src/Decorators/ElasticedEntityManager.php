<?php

namespace DoctrineElastic\Decorators;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\UnitOfWork;
use Elasticsearch\Client;

/**
 * @author Ands
 */
class ElasticedEntityManager extends EntityManagerDecorator {

    protected $repositoryFactory;
    protected $conn;
    protected $config;
    protected $eventManager;
    protected $unitOfWork;
    protected $elastic;

    public function __construct($conn, Configuration $config, Client $elastic, EventManager $eventManager = null) {
        $wrappedEntityManager = EntityManager::create($conn, $config, $eventManager);
        parent::__construct($wrappedEntityManager);

        $this->conn              = $conn;
        $this->config            = $config;
        $this->eventManager      = $eventManager;

        $this->repositoryFactory = new DefaultRepositoryFactory();
        $this->unitOfWork        = new UnitOfWork($this);
        $this->elastic = $elastic;
    }

    public function getUnitOfWork() {
        $uow = new ElasticedUnitOfWork($this, $this->elastic);

        return $uow;
    }

    public function getRepository($className) {
        return $this->repositoryFactory->getRepository($this, $className);
    }

}