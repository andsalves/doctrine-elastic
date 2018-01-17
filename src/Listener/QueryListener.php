<?php

namespace DoctrineElastic\Listener;

use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinColumns;
use Doctrine\ORM\Mapping\ManyToOne;
use DoctrineElastic\Event\QueryEventArgs;
use DoctrineElastic\Hydrate\AnnotationEntityHydrator;

/**
 * Query events main handler for this extension
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class QueryListener
{

    /** @var AnnotationEntityHydrator */
    protected $hydrator;

    public function __construct()
    {
        $this->hydrator = new AnnotationEntityHydrator();
    }

    public function beforeQuery(QueryEventArgs $eventArgs)
    {

    }

    /**
     * @param QueryEventArgs $eventArgs
     */
    public function postQuery(QueryEventArgs $eventArgs)
    {
        $results = $eventArgs->getResults();
        $entityManager = $eventArgs->getEntityManager();
        $targetEntity = $eventArgs->getTargetEntity();

        if (!empty($results) && boolval($entityManager) && boolval($targetEntity)) {
            $this->executeRelationshipQueries($eventArgs);
        }
    }

    /**
     * Makes a heavy work, simulating a doctrine 1-n entity relationship
     * This recourse must be used cautiously. Check if your application really needs that.
     *
     * @param QueryEventArgs $eventArgs
     */
    private function executeRelationshipQueries(QueryEventArgs $eventArgs)
    {
        $targetClass = $eventArgs->getTargetEntity();
        $entityManager = $eventArgs->getEntityManager();
        $results = $eventArgs->getResults();

        /** @var ManyToOne[] $manyToOnes */
        $manyToOnes = $this->hydrator->extractSpecAnnotations($targetClass, ManyToOne::class);
        /** @var JoinColumns[] $joinsColumns */
        $joinsColumns = $this->hydrator->extractSpecAnnotations($targetClass, JoinColumns::class);

        foreach ($manyToOnes as $propName => $mto) {
            if (!isset($joinsColumns[$propName])) {
                continue;
            }

            foreach ($joinsColumns as $joinsColumn) {
                /** @var JoinColumn[] $joinColumns */
                $joinColumns = $joinsColumn->value;
                foreach ($joinColumns as $joinColumn) {
                    $colunmName = AnnotationEntityHydrator::camelizeString($joinColumn->referencedColumnName);
                    foreach ($results as $key => $result) {
                        if (property_exists(get_class($result), $colunmName)) {
                            $value = $this->hydrator->extract($result, $colunmName);
                            $relObject = $entityManager->getRepository($mto->targetEntity)->findOneBy([$colunmName => $value]);
                            $this->hydrator->hydrate($results[$key], [$colunmName => $relObject]);
                        }
                    }
                }
            }
        }

        $eventArgs->setResults($results);
    }

    /**
     * Fetch and inject the parent object to the child ones from query
     *
     * @param QueryEventArgs $eventArgs
     */
    private function insertParentsData(QueryEventArgs $eventArgs)
    {
        $targetClass = $eventArgs->getTargetEntity();
        $entityManager = $eventArgs->getEntityManager();
        $results = $eventArgs->getResults();

        /** @var ManyToOne[] $manyToOnes */
        $manyToOnes = $this->hydrator->extractSpecAnnotations($targetClass, ManyToOne::class);

        foreach ($manyToOnes as $propName => $mto) {
            if (!isset($joinsColumns[$propName])) {
                continue;
            }

            foreach ($joinsColumns as $joinsColumn) {
                /** @var JoinColumn[] $joinColumns */
                $joinColumns = $joinsColumn->value;
                foreach ($joinColumns as $joinColumn) {
                    $colunmName = AnnotationEntityHydrator::camelizeString($joinColumn->referencedColumnName);
                    foreach ($results as $key => $result) {
                        if (property_exists(get_class($result), $colunmName)) {
                            $value = $this->hydrator->extract($result, $colunmName);
                            $relObject = $entityManager->getRepository($mto->targetEntity)->findOneBy([$colunmName => $value]);
                            $this->hydrator->hydrate($results[$key], [$colunmName => $relObject]);
                        }
                    }
                }
            }
        }

        $eventArgs->setResults($results);
    }
}