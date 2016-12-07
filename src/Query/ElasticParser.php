<?php

namespace DoctrineElastic\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parser;
use DoctrineElastic\Elastic\ElasticQuery;

class ElasticParser {

    /** @var ElasticQuery */
    protected $query;

    /** @var Parser */
    private $doctrineParser;

    /** @var Query\AST\SelectStatement */
    private $_ast;

    public function __construct(ElasticQuery $query) {
        $this->query = $query;
        $doctrineQuery = new Query($query->getEntityManager());
        $doctrineQuery->setDQL($query->getDQL());
        $this->doctrineParser = new Parser($doctrineQuery);;
    }

    public function getAST() {
        if (is_null($this->_ast)) {
            $this->_ast = $this->doctrineParser->QueryLanguage();
        }

        return $this->_ast;
    }

    public function parseElasticQuery() {
        $parserResult = new ElasticParserResult();

        $outputWalker = new ElasticWalker($this->query, $this->getAST());
        $searchParams = $outputWalker->walkSelectStatement();

        $parserResult->setElasticExecutor($outputWalker->getExecutor());
        $parserResult->setSearchParams($searchParams);

        return $parserResult;
    }
}