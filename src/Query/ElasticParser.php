<?php

namespace DoctrineElastic\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\TreeWalkerChain;
use DoctrineElastic\Elastic\ElasticQuery;

class ElasticParser {

    /** @var ElasticQuery */
    protected $query;

    /** @var Parser */
    private $doctrineParser;

    /**v@var ElasticParserResult */
    private $parserResult;

    public function __construct(ElasticQuery $query, EntityManagerInterface $entityManager) {
        $this->query = $query;
        $doctrineQuery = new Query($entityManager);
        $doctrineQuery->setDQL($query->getDQL());
        $this->doctrineParser = new Parser($doctrineQuery);;
        $this->parserResult = new ElasticParserResult();
    }

    public function parseElasticQuery() {
        $AST = $this->doctrineParser->getAST();

        $outputWalker = new ElasticWalker($this->query, $this->parserResult);

        // Assign an SQL executor to the parser result
        $this->parserResult->setElasticExecutor($outputWalker->getExecutor($AST));

        return $this->parserResult;
    }
}