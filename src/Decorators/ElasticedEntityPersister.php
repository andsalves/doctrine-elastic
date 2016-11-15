<?php

namespace DoctrineElastic\Decorators;


use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\Column;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Mapping\Type;
use DoctrineElastic\Service\ElasticSearchService;
use Elasticsearch\Client;

class ElasticedEntityPersister extends EntityPersisterDecorator {

    /** @var AnnotationReader */
    private $annotationReader;

    /** @var ElasticSearchService */
    private $elasticSearchService;

    public function __construct(EntityManagerInterface $em, ClassMetadata $class, Client $elastic) {
        parent::__construct($em, $class, $elastic);

        $this->annotationReader = new AnnotationReader();
        $this->elasticSearchService = new ElasticSearchService($elastic);
        $this->validateEntity($class);
    }

    private function validateEntity(ClassMetadataInfo $classMetadata) {
        $type = $this->annotationReader->getClassAnnotation($classMetadata->getReflectionClass(), Type::class);
        if (!($type instanceof Type)) {
            throw new AnnotationException(sprintf('Annotation %s is missing in %s entity class',
                Type::class, $classMetadata->getName()));
        }

        if (!$type->isValid()) {
            $errorMessage = $type->getErrorMessage() . ' in %s entity class';
            throw new AnnotationException(sprintf($errorMessage, $classMetadata->getName()));
        }
    }

    public function load(array $criteria, $entity = null, $assoc = null, array $hints = array(), $lockMode = null, $limit = null, array $orderBy = null) {
        $results = $this->loadAll($criteria, $orderBy, $limit);

        return count($results) ? $results[0] : null;
    }

    public function loadAll(array $criteria = [], array $orderBy = null, $limit = null, $offset = null) {
        $classMetadata = $this->wrapped->getClassMetadata();
        /** @var Type $type */
        $type = $this->annotationReader->getClassAnnotation(
            $classMetadata->getReflectionClass(), Type::class
        );

        $sort = $must = [];
        $body = ['query' => ['bool' => ['must' => $must]]];

        foreach ($classMetadata->getReflectionProperties() as $propertyName => $desc) {
            $reflectionProperty = $classMetadata->getReflectionProperty($propertyName);
            $annotationProperty = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Column::class);

            if (isset($criteria[$propertyName])) {
                $must[] = array(
                    'query_string' => array(
                        'query' => sprintf("%s:%s", $annotationProperty->name, $criteria[$propertyName]),
                        'default_operator' => 'AND'
                    )
                );
            }

            if (isset($orderBy[$propertyName])) {
                $sort[$annotationProperty->name] = $orderBy[$propertyName];
            }
        }

        $body['query']['bool']['must'] = $must;

        $searchParams = new SearchParams();
        $searchParams->setIndex($type->getIndex());
        $searchParams->setType($type->getName());
        $searchParams->setBody($body);
        $searchParams->setSize($limit);
        $searchParams->setSort($sort);
        $searchParams->setFrom($offset);

        $uow = $this->em->getUnitOfWork();
        $resultSets = $this->elasticSearchService->searchAsIterator($searchParams)->getArrayCopy();
        $results = [];

        foreach ($resultSets as $resultSet) {
            $keyedResult = [];

            foreach ($classMetadata->getReflectionProperties() as $propertyName => $desc) {
                $reflectionProperty = $classMetadata->getReflectionProperty($propertyName);
                $annotationProperty = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Column::class);

                if (isset($resultSet[$annotationProperty->name])) {
                    $keyedResult[$propertyName] = $resultSet[$annotationProperty->name];
                }
            }

            $results[] = $uow->createEntity($classMetadata->getName(), $keyedResult);
        }

        return $results;
    }

    public function getAnnotionReader() {
        return $this->annotationReader;
    }
}