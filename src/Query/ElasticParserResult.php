<?php

namespace DoctrineElastic\Query;


class ElasticParserResult {

    /** @var ElasticExecutor */
    protected $executor;

    public function getElasticExecutor() {
        return $this->executor;
    }

    public function setElasticExecutor(ElasticExecutor $executor) {
        $this->executor = $executor;
    }

    public function getParameterMappings() {
        return [];
    }
}