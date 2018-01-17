<?php

namespace DoctrineElastic\Event;

use Doctrine\Common\EventArgs;
use DoctrineElastic\ElasticEntityManager;

/**
 * EventArgs relative to Query operations in DoctrineElastic events
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class QueryEventArgs extends EventArgs
{
    /** @var array */
    protected $results;

    /** @var ElasticEntityManager */
    protected $entityManager;

    /** @var string */
    protected $targetEntity;

    /** @return array */
    public function getResults()
    {
        return $this->results;
    }

    /** @param array $results */
    public function setResults(&$results)
    {
        $this->results = $results;
    }

    /**
     * @return ElasticEntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param ElasticEntityManager $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return string
     */
    public function getTargetEntity()
    {
        return $this->targetEntity;
    }

    /**
     * @param string $targetEntity
     */
    public function setTargetEntity($targetEntity)
    {
        $this->targetEntity = $targetEntity;
    }


}