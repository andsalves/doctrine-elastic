<?php

namespace DoctrineElastic\Hydrate;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use DoctrineElastic\Mapping\Field;
use DoctrineElastic\Mapping\MetaField;

class SimpleEntityHydrator implements SimpleHydratorInterface {

    protected $annotationReader;

    public function __construct() {
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * @param object $entity
     * @param array|mixed $data
     * @param ClassMetadataInfo $classMetadata
     * @return object
     */
    public function hydrate($entity, $data, $classMetadata = null) {
        if ($classMetadata instanceof ClassMetadataInfo) {
            /** @var \ReflectionProperty[] $reflectionProperties */
            $reflectionProperties = $classMetadata->getReflectionProperties();

            foreach ($reflectionProperties as $propertyName => $reflectionProperty) {
                /** @var Field $fieldAnnotation */
                $fieldAnnotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Field::class);
                /** @var MetaField $metaFieldAnnotation */
                $metaFieldAnnotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, MetaField::class);
                $fieldName = false;
                $setMethod = 'set' . ucfirst($propertyName);

                if ($fieldAnnotation) {
                    $fieldName = $fieldAnnotation->name;
                } else if ($metaFieldAnnotation) {
                    $fieldName = $metaFieldAnnotation->name;
                }

                if ($fieldName && array_key_exists($fieldName, $data)) {
                    if (method_exists($entity, $setMethod)) {
                        $entity->$setMethod($data[$fieldName]);
                    } else if ($reflectionProperty->isPublic()) {
                        $entity->$propertyName = $data[$fieldName];
                    }
                }
            }
        }

        return $entity;
    }

    /**
     * @param $entity
     * @param ClassMetadataInfo $classMetadata
     * @param null $specAnnotationClass
     * @return array
     */
    public function extract($entity, $classMetadata = null, $specAnnotationClass = null) {
        $data = [];

        if ($classMetadata instanceof ClassMetadataInfo) {
            /** @var \ReflectionProperty[] $reflectionProperties */
            $reflectionProperties = $classMetadata->getReflectionProperties();

            foreach ($reflectionProperties as $propertyName => $reflectionProperty) {
                $specAnnotation = $fieldAnnotation = $metaFieldAnnotation = false;

                if ($specAnnotationClass) {
                    /** @var Annotation $specAnnotation */
                    $specAnnotation = $this->annotationReader->getPropertyAnnotation(
                        $reflectionProperty, $specAnnotationClass
                    );
                } else {
                    /** @var Field $fieldAnnotation */
                    $fieldAnnotation = $this->annotationReader->getPropertyAnnotation(
                        $reflectionProperty, Field::class
                    );
                    /** @var MetaField $metaFieldAnnotation */
                    $metaFieldAnnotation = $this->annotationReader->getPropertyAnnotation(
                        $reflectionProperty, MetaField::class
                    );
                }

                $getMethod = 'get' . ucfirst($propertyName);
                $fieldName = false;

                if ($fieldAnnotation) {
                    $fieldName = $fieldAnnotation->name;
                } else if ($metaFieldAnnotation) {
                    $fieldName = $metaFieldAnnotation->name;
                } else if ($specAnnotation && isset($specAnnotation->name)) {
                    $fieldName = $specAnnotation->name;
                }

                if ($fieldName) {
                    if (method_exists($entity, $getMethod)) {
                        $data[$fieldName] = $entity->$getMethod();
                    } else if ($reflectionProperty->isPublic()) {
                        $data[$fieldName] = $entity->$propertyName;
                    }
                }
            }
        }

        return $data;
    }
}