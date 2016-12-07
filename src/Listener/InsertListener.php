<?php

namespace DoctrineElastic\Listener;

use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinColumns;
use Doctrine\ORM\Mapping\ManyToOne;
use DoctrineElastic\Event\EntityEventArgs;
use DoctrineElastic\Exception\ElasticConstraintException;
use DoctrineElastic\Hydrate\AnnotationEntityHydrator;

/**
 * @author Ands
 */
class InsertListener {

    /** @var AnnotationEntityHydrator */
    private $hydrator;


    public function beforeInsert(EntityEventArgs $eventArgs) {
        $entity = $eventArgs->getEntity();

        if (!is_object($entity)) {
            return null;
        }

        $this->changeEntityForManyToOneRelationships($entity);
    }

    public function postInsert(EntityEventArgs $eventArgs) {

    }

    private function changeEntityForManyToOneRelationships($entity) {
        /** @var ManyToOne[] $relationships */
        $relationships = $this->getHydrator()->extractSpecAnnotations($entity, ManyToOne::class);
        /** @var JoinColumns[] $joinColumns */
        $joinColumns = $this->getHydrator()->extractSpecAnnotations($entity, JoinColumns::class);

        if (empty($relationships)) {
            return null;
        }

        foreach ($relationships as $propertyName => $relationship) {
            if (!isset($joinColumns[$propertyName]) || empty($joinColumns[$propertyName]->value)) {
                continue;
            }

            /** @var JoinColumn $joinColumn */
            $joinColumn = reset($joinColumns[$propertyName]->value);

            $relEntity = $this->getHydrator()->extract($entity, $joinColumn->name);

            if (!is_object($relEntity)) {
                continue;
            }

            $finalValue = $this->getHydrator()->extract($relEntity, $joinColumn->referencedColumnName);
            if (is_null($finalValue)) {
                throw new ElasticConstraintException(sprintf('Entity class %s has relationship '
                    . "through referenced property '%s', "
                    . 'but it is null or does not exist in %s target entity',
                    get_class($entity), $joinColumn->referencedColumnName, get_class($relEntity)));
            }

            $this->getHydrator()->hydrate($entity, [$propertyName => $finalValue]);
        }
    }

    private function getHydrator() {
        if (is_null($this->hydrator)) {
            $this->hydrator = new AnnotationEntityHydrator();
        }

        return $this->hydrator;
    }
}