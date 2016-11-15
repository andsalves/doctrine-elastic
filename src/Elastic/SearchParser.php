<?php

namespace DoctrineElastic\Elastic;

class SearchParser {

    /**
     * @param SearchParams $searchParams
     * @return array
     */
    public static function parseSearchParams(SearchParams $searchParams) {
        $elasticQuerySearch = array(
            'index' => $searchParams->getIndex(),
            'type' => $searchParams->getType(),
            'body' => $searchParams->getBody()
        );

        if (is_numeric($searchParams->getSize())) {
            $elasticQuerySearch['size'] = $searchParams->getSize();
        }

        foreach ($searchParams->getSort() as $field => $value) {
            $elasticQuerySearch['body']['sort'][] = [$field => $value];
        }

        if ($searchParams->getFrom()) {
            $elasticQuerySearch['from'] = $searchParams->getFrom();
        }

        return $elasticQuerySearch;
    }

}