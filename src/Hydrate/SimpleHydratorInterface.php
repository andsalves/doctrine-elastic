<?php

namespace DoctrineElastic\Hydrate;

/**
 * Interface for Hydrators
 *
 * @author Ands
 */
interface SimpleHydratorInterface {

    /**
     * @param object $entity
     * @param array|mixed $data
     * @return object
     */
    public function hydrate($entity, array $data);

    /**
     * @param $object
     * @param $fieldOrFields
     * @return array
     */
    public function extract($object, $fieldOrFields = null);
}