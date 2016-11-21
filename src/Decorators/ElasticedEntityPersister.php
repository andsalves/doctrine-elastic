<?php

namespace DoctrineElastic\Decorators;


use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappingException;
use DoctrineElastic\Elastic\FieldTypes;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Exception\ElasticOperationException;
use DoctrineElastic\Hydrate\SimpleEntityHydrator;
use DoctrineElastic\Mapping\Field;
use DoctrineElastic\Mapping\MetaField;
use DoctrineElastic\Mapping\Type;
use DoctrineElastic\Service\ElasticSearchService;
use Elasticsearch\Client;

class ElasticedEntityPersister extends AbstractEntityPersister {

    /** @var AnnotationReader */
    private $annotationReader;

    /** @var ElasticSearchService */
    private $elasticSearchService;

    /** @var array */
    protected $queuedInserts = [];

    /** @var SimpleEntityHydrator */
    private $hydrator;

    public function __construct(EntityManagerInterface $em, ClassMetadata $class, Client $elastic) {
        parent::__construct($em, $class, $elastic);
        $this->annotationReader = new AnnotationReader();
        $this->elasticSearchService = new ElasticSearchService($elastic);
        $this->hydrator = new SimpleEntityHydrator();
        $this->validateEntity($class);
    }

    private function validateEntity(ClassMetadataInfo $classMetadata) {
        $type = $this->annotationReader->getClassAnnotation($classMetadata->getReflectionClass(), Type::class);
        $className = $classMetadata->name;

        if (!($type instanceof Type)) {
            throw new AnnotationException(sprintf('Annotation %s is missing in %s entity class',
                Type::class, $classMetadata->getName()));
        }

        if (!$type->isValid()) {
            $errorMessage = $type->getErrorMessage() . ' in %s entity class';
            throw new AnnotationException(sprintf($errorMessage, $classMetadata->getName()));
        }

        $metaFields = $this->getAnnotationsProperties(MetaField::class);
        $has_id = false;

        foreach ($metaFields as $propertyName => $metaField) {
            if ($metaField->name == '_id') {
                if ($propertyName != '_id') {
                    throw new AnnotationException('_id field must have same name for propertyName');
                }

                $reflectionProperty = $classMetadata->getReflectionProperty($propertyName);
                if ($reflectionProperty->isPublic()
                    || method_exists(new $className(), 'set_id')
                ) {
                    $has_id = true;
                } else {
                    $errorMessage = '_id metaField must to be public or have a set_id method';
                    throw new AnnotationException(sprintf($errorMessage, $classMetadata->getName()));
                }
            }
        }

        if (!$has_id) {
            $errorMessage = '_id metaField is missing in %s entity class';
            throw new AnnotationException(sprintf($errorMessage, $classMetadata->getName()));
        }
    }

    public function load(array $criteria, $entity = null, $assoc = null, array $hints = array(), $lockMode = null, $limit = null, array $orderBy = null) {
        $results = $this->loadAll($criteria, $orderBy, $limit);

        return count($results) ? $results[0] : null;
    }

    public function loadAll(array $criteria = [], array $orderBy = null, $limit = null, $offset = null) {
        $classMetadata = $this->getClassMetadata();
        /** @var Type $type */
        $type = $this->annotationReader->getClassAnnotation(
            $classMetadata->getReflectionClass(), Type::class
        );

        $sort = $must = [];
        $body = ['query' => ['bool' => ['must' => $must]]];

        foreach ($classMetadata->getReflectionProperties() as $propertyName => $reflectionProperty) {
            $annotationProperty = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Column::class);

            if (isset($criteria[$propertyName])) {
                $must[] = array(
                    'match' => array(
                        $annotationProperty->name => $criteria[$propertyName],
                    )
                );
            }

            if (isset($orderBy[$propertyName])) {
                $sort[$annotationProperty->name] = $orderBy[$propertyName];
            }
        }

        $body['query']['bool']['must'] = $must;

        $searchParams = new SearchParams();
        $searchParams->setIndex($type->getIndex());
        $searchParams->setType($type->getName());
        $searchParams->setBody($body);
        $searchParams->setSize($limit);
        $searchParams->setSort($sort);
        $searchParams->setFrom($offset);

        $arrayResults = $this->elasticSearchService->searchAsIterator($searchParams)->getArrayCopy();
        $results = [];
        $className = $classMetadata->name;

        foreach ($arrayResults as $arrayResult) {
            $entity = new $className();
            $results[] = $this->hydrator->hydrate($entity, $arrayResult, $classMetadata);
        }

        return $results;
    }

    public function getAnnotionReader() {
        return $this->annotationReader;
    }

    public function executeInserts() {
        foreach ($this->queuedInserts as $entity) {
            $type = $this->getEntityType();

            $classMetadata = $this->getClassMetadata();

            $fieldsData = $this->hydrator->extract($entity, $classMetadata, Field::class);
            $metaFieldsData = $this->hydrator->extract($entity, $classMetadata, MetaField::class);
            $mergeParams = [];

            if (isset($metaFieldsData['_id'])) {
                $mergeParams['id'] = $metaFieldsData['_id'];
            }

            $this->createTypeIfNotExists();
            $return = [];

            $inserted = $this->em->getConnection()->insert(
                $type->getIndex(), $type->getName(), $fieldsData, $mergeParams, $return
            );

            if ($inserted) {
                $this->hydrateEntityBySearchResult($entity, $return);
            } else {
                throw new ElasticOperationException(sprintf('Unable to complete update operation, '
                    . 'with the following elastic return: <br><pre>%s</pre>', var_export($return)));
            }

        }
    }

    private function getEntity_id($entity) {
        /** @var MetaField[] $metaFields */
        $metaFields = $this->getAnnotationsProperties(MetaField::class);

        foreach ($metaFields as $propertyName => $metaField) {
            if ($metaField->name == '_id') {
                $getMethod = 'get' . ucfirst($propertyName);
                if (method_exists($entity, $getMethod)) {
                    return $entity->$getMethod();
                } else {
                    $reflectionProperty = $this->class->getReflectionProperty($propertyName);
                    if ($reflectionProperty->isPublic()) {
                        return $entity->$propertyName;
                    }
                }
            }
        }

        return null;
    }

    public function update($entity) {
        $type = $this->getEntityType();

        $classMetadata = $this->getClassMetadata();

        $dataUpdate = [];
        $entityData = $this->hydrator->extract($entity, $classMetadata);
        $fields = $this->getAnnotationsProperties(Field::class);

        foreach ($fields as $field) {
            if (isset($entityData[$field->name])) {
                $dataUpdate[$field->name] = $entityData[$field->name];
            }
        }

        $_id = $this->getEntity_id($entity);

        $return = [];
        $updated = $this->em->getConnection()->update(
            $type->getIndex(), $type->getName(), $_id, $dataUpdate, [], $return
        );

        if ($updated) {
            $metaFields = $this->getAnnotationsProperties(MetaField::class);

            foreach ($metaFields as $propertyName => $metaField) {
                if (isset($return[$metaField->name])) {
                    $reflectionProperty = $classMetadata->getReflectionProperty($propertyName);
                    if ($reflectionProperty->isPublic()) {
                        $entity->$propertyName = $return[$metaField->name];
                    } else {
                        $setMethod = 'set' . ucfirst($propertyName);
                        if (method_exists($entity, $setMethod)) {
                            $entity->$setMethod($return[$metaField->name]);
                        }
                    }
                }
            }
        } else {
            throw new ElasticOperationException(sprintf('Unable to complete update operation, '
                . 'with the following elastic return: <br><pre>%s</pre>', var_export($return)));
        }
    }

    private function getAnnotationsProperties($annotationClass) {
        $classMetadata = $this->getClassMetadata();
        $annotations = [];

        foreach ($classMetadata->getReflectionProperties() as $propertyName => $reflectionProperty) {
            $annotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, $annotationClass);

            if ($annotation instanceof $annotationClass) {
                $annotations[$propertyName] = $annotation;
            }
        }

        return $annotations;
    }

    private function createTypeIfNotExists() {
        $type = $this->getEntityType();
        $indexName = $type->getIndex();
        $typeName = $type->getName();
        $classMetadata = $this->getClassMetadata();

        if (!$this->em->getConnection()->typeExists($indexName, $typeName)) {
            $propertiesMapping = [];

            foreach ($classMetadata->getReflectionProperties() as $propertyName => $reflectionProperty) {
                /** @var MetaField $ESMetaField */
                $ESMetaField = $this->annotationReader->getPropertyAnnotation($reflectionProperty, MetaField::class);

                if ($ESMetaField instanceof MetaField) {
                    continue;
                }

                /** @var Column $column */
                $column = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Column::class);
                /** @var Field $ESField */
                $ESField = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Field::class);
                $fieldType = FieldTypes::doctrineToElastic($column->type);

                $propertiesMapping[$column->name] = ['type' => $fieldType];

                if ($ESField instanceof Field) {
                    foreach ($ESField->getArrayCopy() as $prop => $propValue) {
                        if (!is_null($propValue) && $prop != 'name') {
                            $propertiesMapping[$column->name][$prop] = $propValue;
                        }
                    }
                }
            }

            $mappings = array(
                $typeName => array(
                    'properties' => $propertiesMapping
                )
            );

            if (!$this->em->getConnection()->indexExists($indexName)) {
                $this->em->getConnection()->createIndex($indexName, $mappings);
            } else {
                $this->em->getConnection()->createType($indexName, $typeName, $mappings);
            }
        }
    }

    public function addInsert($entity) {
        $oid = spl_object_hash($entity);
        $this->queuedInserts[$oid] = $entity;
    }


    /**
     * @return Type
     * @throws MappingException
     */
    private function getEntityType() {
        $type = $this->annotationReader->getClassAnnotation(
            $this->getClassMetadata()->getReflectionClass(), Type::class
        );

        if ($type instanceof Type) {
            return $type;
        } else {
            throw new MappingException(sprintf('Unable to get Type Mapping of %s entity', $this->class->name));
        }
    }

    public function loadById(array $_idArray, $entity = null) {
        $type = $this->getEntityType();

        if (is_object($entity) && get_class($entity) != $this->class->name) {
            throw new \InvalidArgumentException('You can only get an element by _id with its properly persister');
        }

        $id = isset($_idArray['_id']) ? $_idArray['_id'] : reset($_idArray);

        $documentData = $this->em->getConnection()->get($type->getIndex(), $type->getName(), $id);

        if ($documentData) {
            $entity = is_object($entity) ? $entity : new $this->class->name;
            $this->hydrateEntityBySearchResult($entity, $documentData);

            return $entity;
        }

        return null;
    }

    private function hydrateEntityBySearchResult($entity, array $searchResult) {
        $hydrator = new SimpleEntityHydrator();

        $hydrator->hydrate($entity, $searchResult, $this->class);

        if (isset($searchResult['_source'])) {
            $hydrator->hydrate($entity, $searchResult['_source'], $this->class);
        }
    }
}