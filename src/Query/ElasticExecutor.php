<?php

namespace DoctrineElastic\Query;

use Doctrine\ORM\Query\AST\SelectStatement;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Hydrate\SimpleEntityHydrator;
use DoctrineElastic\Service\ElasticSearchService;

class ElasticExecutor {

    /** @var SimpleEntityHydrator */
    private $_hydrator;

    /** @var ElasticSearchService */
    private $_searchService;

    /** @var string */
    private $_className;

    public function __construct(ElasticSearchService $searchService, $className) {
        $this->_searchService = $searchService;
        $this->_hydrator = new SimpleEntityHydrator();
        $this->_className = $className;
    }

    public function execute(SearchParams $searchParams) {
        if (!$searchParams->isValid()) {
            throw new \InvalidArgumentException('Elastic search params are invalid for request. ');
        }

        $className = $this->_className;
        $resultSets = $this->_searchService->searchAsIterator($searchParams)->getArrayCopy();
        $results = [];

        foreach ($resultSets as $resultSet) {
            $entity = new $className();
            $this->_hydrator->hydrate($entity, $resultSet);
            $results[] = $entity;
        }

        return $results;
    }
}