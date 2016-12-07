<?php

namespace DoctrineElastic\Hydrate;

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