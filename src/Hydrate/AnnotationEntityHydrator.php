<?php

namespace DoctrineElastic\Hydrate;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationReader;
use DoctrineElastic\Mapping\Field;

/**
 * Hydrator expert at extracting annotation fields from Entities
 * Uses AnnotationReader from Doctrine
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class AnnotationEntityHydrator extends SimpleEntityHydrator {

    /** @var AnnotationReader */
    protected $annotationReader;

    public function __construct() {
        parent::__construct();
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * Extract fields with specified annotation
     *
     * @param $entity
     * @param null $specAnnotationClass
     * @return array
     */
    public function extractWithAnnotation($entity, $specAnnotationClass = Field::class) {
        $properties = $this->reflectionPropertiesGetter->getProperties(get_class($entity));
        $values = $this->extract($entity);
        $data = [];

        foreach ($properties as $prop) {
            /** @var Annotation|Field $specAnnotation */
            $specAnnotation = $this->annotationReader->getPropertyAnnotation(
                $prop, $specAnnotationClass
            );

            $name = self::decamelizeString($prop->name);

            if (!is_null($specAnnotation) && isset($specAnnotation->name) && in_array($name, array_keys($values))) {
                $data[$specAnnotation->name] = $values[$name];
            }
        }

        return $data;
    }

    /**
     * @param object $entity
     * @param string $annotationClass
     * @param array $data
     * @return object
     */
    public function hydrateByAnnotation($entity, $annotationClass, array $data) {
        /** @var Annotation $annotations */
        $annotations = $this->extractSpecAnnotations(get_class($entity), $annotationClass);
        $dataAnnotations = [];

        foreach ($annotations as $propName => $annotation) {
            if (!property_exists($annotation, 'name')) {
                continue;
            }

            if (isset($data[$annotation->name])) {
                $dataAnnotations[$propName] = $data[$annotation->name];
            }
        }

        return $this->hydrate($entity, $dataAnnotations);
    }

    /**
     * Extract annotations from entity fields
     *
     * @param $entityClass
     * @param string $specAnnotationClass
     * @return array|\Doctrine\ORM\Mapping\Annotation[] {$specAnnotationClass}[]
     */
    public function extractSpecAnnotations($entityClass, $specAnnotationClass) {
        $properties = $this->reflectionPropertiesGetter->getProperties($entityClass);
        $annotations = [];

        foreach ($properties as $prop) {
            /** @var Annotation $specAnnotation */
            $specAnnotation = $this->annotationReader->getPropertyAnnotation(
                $prop, $specAnnotationClass
            );

            if ($specAnnotation) {
                $annotations[$prop->name] = $specAnnotation;
            }
        }

        return $annotations;
    }
}
