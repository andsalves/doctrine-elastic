<?php

namespace DoctrineElastic\Hydrate;

interface SimpleHydratorInterface {

    /**
     * @param object $entity
     * @param array|mixed $data
     * @param mixed $metadata
     * @return object
     */
    public function hydrate($entity, $data, $metadata = null);

    /**
     * @param $object
     * @param mixed $metadata
     * @return array
     */
    public function extract($object, $metadata = null);
}