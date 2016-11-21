<?php

namespace DoctrineElastic\Query;

use Doctrine\ORM\Query\AST\SelectStatement;
use DoctrineElastic\Decorators\ElasticEntityManager;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Hydrate\SimpleEntityHydrator;
use DoctrineElastic\Service\ElasticSearchService;

class ElasticExecutor {

    /** @var SearchParams */
    private $_searchParams;

    /** @var SelectStatement */
    private $_ast;

    /** @var string */
    private $_className;

    /** @var SimpleEntityHydrator */
    private $_hydrator;

    public function __construct(SearchParams $searchParams, SelectStatement $AST, $className) {
        if (!$searchParams->isValid()) {
            throw new \InvalidArgumentException('Elastic search params are invalid for request. ');
        }

        $this->_searchParams = $searchParams;
        $this->_ast = $AST;
        $this->_className = $className;
        $this->_hydrator = new SimpleEntityHydrator();
    }

    public function execute(
        ElasticSearchService $searchService, ElasticEntityManager $em
    ) {
        $resultSets = $searchService->searchAsIterator($this->_searchParams)->getArrayCopy();
        $results = [];
        $className = $this->_className;
        $classMetadata = $em->getClassMetadata($className);

        foreach ($resultSets as $resultSet) {
            $entity = new $className();
            $this->_hydrator->hydrate($entity, $resultSet, $classMetadata);
            $results[] = $entity;
        }

        return $results;
    }
}