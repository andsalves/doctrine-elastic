<?php

namespace DoctrineElastic\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Query\AST\SelectStatement;
use DoctrineElastic\Elastic\ElasticQuery;

class QueryEventArgs extends EventArgs {

    /** @var SelectStatement */
    protected $_ast;

    /** @var ElasticQuery */
    protected $query;

    /** @var array */
    protected $results;

    public function __construct(ElasticQuery $query) {
        $this->query = $query;
    }

    /** @return SelectStatement */
    public function getAST() {
        return $this->_ast;
    }

    /** @param SelectStatement $AST */
    public function setAST($AST) {
        $this->_ast = $AST;
    }

    /** @return ElasticQuery */
    public function getQuery() {
        return $this->query;
    }

    /** @param ElasticQuery $query */
    public function setQuery($query) {
        $this->query = $query;
    }

    /** @return array */
    public function getResults() {
        return $this->results;
    }

    /** @param array $results */
    public function setResults($results) {
        $this->results = $results;
    }


}