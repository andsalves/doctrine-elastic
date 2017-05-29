<?php

namespace DoctrineElastic\Repository;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Entity;
use DoctrineElastic\ElasticEntityManager;

/**
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class ElasticRepositoryManager {

    /** @var ElasticEntityRepository[] */
    protected $repositoryList;

    /** @var AnnotationReader */
    protected $annotationReader;

    public function getRepository(ElasticEntityManager $entityManager, $className) {
        $hashKey = md5($className) . spl_object_hash($entityManager);

        if (!isset($this->repositoryList[$hashKey])) {
            $repositoryClass = ElasticEntityRepository::class;

            /** @var Entity $entityAnnotation */
            $entityAnnotation = $this->getAnnotationReader()->getClassAnnotation(
                new \ReflectionClass($className), Entity::class
            );
            if (!is_null($entityAnnotation) && isset($entityAnnotation->repositoryClass)) {
                $repositoryClass = $entityAnnotation->repositoryClass;
            }

            $repository = new $repositoryClass($entityManager, $className);
            $this->repositoryList[$hashKey] = $repository;
        }

        return $this->repositoryList[$hashKey];
    }

    public function getAnnotationReader() {
        if (is_null($this->annotationReader)) {
            $this->annotationReader = new AnnotationReader();
        }

        return $this->annotationReader;
    }
}
