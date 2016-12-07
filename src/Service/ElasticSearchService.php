<?php

namespace DoctrineElastic\Service;

use DoctrineElastic\Connection\ElasticConnectionInterface;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Elastic\SearchParser;
use Elasticsearch\Client;

class ElasticSearchService {

    /** @var Client */
    private $elastic;

    public function __construct(ElasticConnectionInterface $elasticConnection) {
        $this->elastic = $elasticConnection->getElasticClient();
    }

    /**
     * @param SearchParams $searchParams
     * @throws \RuntimeException
     * @return \ArrayIterator
     */
    public function searchAsIterator(SearchParams $searchParams) {
        $iterator = new \ArrayIterator();

        if ($this->elastic->indices()->exists(['index' => $searchParams->getIndex()])) {
            $arrayParams = SearchParser::parseSearchParams($searchParams);
            $results = $this->elastic->search($arrayParams);

            if (isset($results['hits']['hits'])) {
                foreach ($results['hits']['hits'] as $rowData) {
                    $data = $rowData['_source'];
                    $data['_id'] = $rowData['_id'];

                    if(isset($results['aggregations'])) {
                        $aggregations = $results['aggregations'];
                        $iterator->append($aggregations);
                        unset($results['aggregations']);
                    }

                    $iterator->append($data);
                }
            }
        }

        return $iterator;

    }
}