<?php

namespace DoctrineElastic\Query;

use DoctrineElastic\Elastic\DoctrineElasticEvents;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Elastic\SearchParser;
use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Event\QueryEventArgs;
use DoctrineElastic\Hydrate\AnnotationEntityHydrator;
use DoctrineElastic\Mapping\Field;
use DoctrineElastic\Mapping\MetaField;

/**
 * Class for query execution with entity hydration task
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class ElasticQueryExecutor {

    /** @var AnnotationEntityHydrator */
    private $_hydrator;

    /** @var ElasticEntityManager */
    private $_eem;

    public function __construct(ElasticEntityManager $elasticEntityManager) {
        $this->_eem = $elasticEntityManager;
        $this->_hydrator = new AnnotationEntityHydrator();
    }

    /**
     * Executes a elastic query from $searchParams, creates a hydrated entity $entityClass object
     * with the results
     *
     * @param SearchParams $searchParams
     * @param $entityClass
     * @return array
     */
    public function execute(SearchParams $searchParams, $entityClass) {
        if (!$searchParams->isValid()) {
            throw new \InvalidArgumentException('Elastic search params are invalid for request. ');
        }

        $eventArgs = $this->createEventArgs($entityClass);
        $this->_eem->getEventManager()->dispatchEvent(DoctrineElasticEvents::beforeQuery, $eventArgs);

        $resultSets = $this->fetchElasticResult($searchParams);
        $results = [];

        foreach ($resultSets as $resultSet) {
            $results[] = $this->hydrateEntityWith(new $entityClass(), $resultSet);
        }

        $eventArgs->setResults($results);
        $this->_eem->getEventManager()->dispatchEvent(DoctrineElasticEvents::postQuery, $eventArgs);

        return $results;
    }

    private function hydrateEntityWith($entity, array $rawData) {
        // $this->_hydrator->hydrateByAnnotation($entity, Field::class, $rawData);
        // $this->_hydrator->hydrateByAnnotation($entity, MetaField::class, $rawData);

        if (isset($rawData['_source'])) {
            $rawData['_source']['_id'] = $rawData['_id'];
            $this->hydrateEntityWith($entity, $rawData['_source']);
        }
        
        $this->_hydrator->hydrate($entity, $rawData);

        return $entity;
    }

    private function fetchElasticResult(SearchParams $searchParams) {
        $results = [];
        $connection = $this->_eem->getConnection();

        if ($connection->indexExists($searchParams->getIndex())) {
            $arrayParams = SearchParser::parseSearchParams($searchParams);
            $results = $connection->search(
                $arrayParams['index'], $arrayParams['type'], $arrayParams['body'], array(
                    'size' => $searchParams->getSize(),
                    'from' => $searchParams->getFrom()
                )
            );
        }

        return $results;
    }

    /**
     * @param $entityClass
     * @return QueryEventArgs
     */
    private function createEventArgs($entityClass) {
        $eventArgs = new QueryEventArgs();
        $eventArgs->setTargetEntity($entityClass);
        $eventArgs->setEntityManager($this->_eem);

        return $eventArgs;
    }
}
