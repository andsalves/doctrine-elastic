<?php

namespace DoctrineElastic\Repository;

use DoctrineElastic\ElasticEntityManager;

/**
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class ElasticRepositoryManager {

    /** @var ElasticEntityRepository[] */
    protected $repositoryList;

    public function getRepository(ElasticEntityManager $entityManager, $className) {
        $hashKey = md5($className) . spl_object_hash($entityManager);

        if (!isset($this->repositoryList[$hashKey])) {
            $repository = new ElasticEntityRepository($entityManager, $className);
            $this->repositoryList[$hashKey] = $repository;
        }

        return $this->repositoryList[$hashKey];
    }
}