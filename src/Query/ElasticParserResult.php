<?php

namespace DoctrineElastic\Query;

use DoctrineElastic\Elastic\SearchParams;

/**
 * Represents a parser result
 */
class ElasticParserResult {

    /** @var ElasticExecutor */
    protected $executor;

    /** @var SearchParams */
    protected $searchParams;

    public function getElasticExecutor() {
        return $this->executor;
    }

    public function setElasticExecutor(ElasticExecutor $executor) {
        $this->executor = $executor;
    }

    public function getParameterMappings() {
        return [];
    }

    /**
     * @return SearchParams
     */
    public function getSearchParams() {
        return $this->searchParams;
    }

    /**
     * @param SearchParams $searchParams
     */
    public function setSearchParams(SearchParams $searchParams) {
        $this->searchParams = $searchParams;
    }
}