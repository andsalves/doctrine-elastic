<?php

namespace DoctrineElastic\Elastic;

use Doctrine\ORM\QueryBuilder;

class ElasticQueryBuilder extends QueryBuilder {

    /**
     * @return ElasticQuery
     */
    public function getQuery() {
        $parameters = $this->getParameters();
        $parameters = clone $parameters;

        $query = $this->getEntityManager()->createQuery($this->getDQL())
            ->setParameters($parameters)
            ->setFirstResult($this->getFirstResult())
            ->setMaxResults($this->getMaxResults());

        return $query;
    }

}