<?php

namespace DoctrineElastic\Query;

use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Hydrate\AnnotationEntityHydrator;
use DoctrineElastic\Hydrate\SimpleEntityHydrator;
use DoctrineElastic\Mapping\Field;
use DoctrineElastic\Mapping\MetaField;
use DoctrineElastic\Service\ElasticSearchService;

/**
 * Class for query execution task
 *
 * @author Ands
 */
class ElasticExecutor {

    /** @var AnnotationEntityHydrator */
    private $_hydrator;

    /** @var ElasticSearchService */
    private $_searchService;

    /** @var string */
    private $_className;

    public function __construct(ElasticSearchService $searchService, $className) {
        $this->_searchService = $searchService;
        $this->_hydrator = new AnnotationEntityHydrator();
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
            $this->_hydrator->hydrateByAnnotation($entity, Field::class, $resultSet);
            $this->_hydrator->hydrateByAnnotation($entity, MetaField::class, $resultSet);
            $results[] = $entity;
        }

        return $results;
    }
}
