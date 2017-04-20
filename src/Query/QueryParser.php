<?php

namespace DoctrineElastic\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parser;
use DoctrineElastic\Elastic\ElasticQuery;

/**
 * Elastic Query parser
 * Prepares query for execution
 *
 * @author Ands
 */
class QueryParser {

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

    /**
     * Converts ElasticQuery to SearchParams
     *
     * @return \DoctrineElastic\Elastic\SearchParams
     */
    public function parseElasticQuery() {
        $outputWalker = new ElasticWalker($this->query, $this->getAST(), $this->getRootClass());

        return $outputWalker->walkSelectStatement();
    }

    /**
     * @return string
     */
    public function getRootClass() {
        /** @var \Doctrine\ORM\Query\AST\IdentificationVariableDeclaration[] $identificationVariableDeclarations */
        $identificationVariableDeclarations = $this->getAST()->fromClause->identificationVariableDeclarations;

        foreach ($identificationVariableDeclarations as $idVarDeclaration) {
            if ($idVarDeclaration->rangeVariableDeclaration->isRoot) {
                return $idVarDeclaration->rangeVariableDeclaration->abstractSchemaName;
            }
        }

        return reset($identificationVariableDeclarations)->rangeVariableDeclaration->abstractSchemaName;
    }
}
