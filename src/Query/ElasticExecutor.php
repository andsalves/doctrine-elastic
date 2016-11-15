<?php

namespace DoctrineElastic\Query;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Query\AST\SelectStatement;
use DoctrineElastic\Decorators\ElasticEntityManager;
use DoctrineElastic\Elastic\InvalidParamsException;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Service\ElasticSearchService;

class ElasticExecutor {

    /** @var SearchParams */
    private $_searchParams;

    /** @var SelectStatement */
    private $_ast;

    /** @var string */
    private $_className;

    public function __construct(SearchParams $searchParams, SelectStatement $AST, $className) {
        if (!$searchParams->isValid()) {
            throw new InvalidParamsException('Elastic search params are invalid for request. ');
        }

        $this->_searchParams = $searchParams;
        $this->_ast = $AST;
        $this->_className = $className;
    }

    public function execute(
        ElasticSearchService $searchService, ElasticEntityManager $entityManager
    ) {
        $uow = $entityManager->getUnitOfWork();
        $resultSets = $searchService->searchAsIterator($this->_searchParams)->getArrayCopy();
        $results = [];
        $classMetadata = $entityManager->getClassMetadata($this->_className);
        $persister = $uow->getEntityPersister($classMetadata->getReflectionClass()->getName());

        foreach ($resultSets as $resultSet) {
            $keyedResult = [];

            foreach ($classMetadata->getReflectionProperties() as $propertyName => $desc) {
                $reflectionProperty = $classMetadata->getReflectionProperty($propertyName);
                $annotationProperty = $persister->getAnnotionReader()->getPropertyAnnotation($reflectionProperty, Column::class);

                if (isset($resultSet[$annotationProperty->name])) {
                    $keyedResult[$propertyName] = $resultSet[$annotationProperty->name];
                }
            }

            $results[] = $uow->createEntity($classMetadata->getName(), $keyedResult);
        }

        return $results;
    }
}