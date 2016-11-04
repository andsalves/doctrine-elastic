<?php

namespace DoctrineElastic\Elastic;

use Doctrine\ORM\Persisters\Entity\EntityPersister;
use DoctrineElastic\Decorators\ElasticedUnitOfWork;

class SearchParser {

    /**
     * @param SearchParamsInterface $searchParams
     * @return array
     */
    public static function parseSearchParams(SearchParamsInterface $searchParams) {
        $elasticQuerySearch = array(
            'index' => $searchParams->getIndex(),
            'type' => $searchParams->getType(),
            'body' => []
        );

        $must = [];
        $filter = [];

        if (is_numeric($searchParams->getSize())) {
            $elasticQuerySearch['size'] = $searchParams->getSize();
        }

        foreach ($searchParams->getBody() as $field => $value) {
            $must[] = array(
                'query_string' => array(
                    'query' => "$field:$value",
                    'default_operator' => 'AND',
                )
            );
        }

        if (!empty($must) || !empty($filter)) {
            $elasticQuerySearch['body'] = array(
                'query' => array(
                    'bool' => ['must' => $must, 'filter' => $filter]
                ),
            );
        }

        return $elasticQuerySearch;
    }

}