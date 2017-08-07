<?php

namespace DoctrineElastic\Persister;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\MappingException;
use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Elastic\DoctrineElasticEvents;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Event\EntityEventArgs;
use DoctrineElastic\Exception\ElasticConstraintException;
use DoctrineElastic\Exception\ElasticOperationException;
use DoctrineElastic\Exception\InvalidParamsException;
use DoctrineElastic\Hydrate\AnnotationEntityHydrator;
use DoctrineElastic\Mapping\Constraint;
use DoctrineElastic\Mapping\Field;
use DoctrineElastic\Mapping\MetaField;
use DoctrineElastic\Mapping\Type;
use DoctrineElastic\Query\ElasticQueryExecutor;

/**
 * Entity Persister for this doctrine elastic extension
 * This class implements some crud operations
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class ElasticEntityPersister {

    /** @var array */
    protected $queuedInserts = [];

    /** @var AnnotationReader */
    private $annotationReader;

    /** @var ElasticQueryExecutor */
    private $queryExecutor;

    /** @var AnnotationEntityHydrator */
    private $hydrator;

    /** @var string */
    private $className;

    /** @var \ReflectionClass */
    private $reflectionClass;

    /** @var ElasticEntityManager */
    private $em;

    public function __construct(ElasticEntityManager $em, $className) {
        $this->className = $className;
        $this->annotationReader = new AnnotationReader();
        $this->queryExecutor = new ElasticQueryExecutor($em);
        $this->hydrator = new AnnotationEntityHydrator();
        $this->validateEntity();
        $this->em = $em;
    }

    public function getReflectionClass() {
        if (is_null($this->reflectionClass)) {
            $this->reflectionClass = new \ReflectionClass($this->className);
        }

        return $this->reflectionClass;
    }

    private function validateEntity() {
        $type = $this->annotationReader->getClassAnnotation($this->getReflectionClass(), Type::class);
        $className = $this->className;

        if (!($type instanceof Type)) {
            throw new AnnotationException(sprintf('%s annotation is missing for %s entity class',
                Type::class, get_class()));
        }

        if (!$type->isValid()) {
            $errorMessage = $type->getErrorMessage() . ' for %s entity class';
            throw new AnnotationException(sprintf($errorMessage, $className));
        }

        $_idSearch = $this->hydrator->extractWithAnnotation(new $className(), MetaField::class);
        $has_id = !empty($_idSearch);

        if (!$has_id) {
            $errorMessage = '_id metaField is missing in %s entity class';
            throw new AnnotationException(sprintf($errorMessage, $className));
        }
    }

    public function loadAll(array $criteria = [], array $orderBy = null, $limit = null, $offset = null) {
        $type = $this->getEntityType();
        $sort = $must = [];
        $body = ['query' => ['bool' => ['must' => $must]]];
        /** @var Field $annotationProperty */
        $fieldAnnotations = $this->hydrator->extractSpecAnnotations($this->className, Field::class);
        /** @var MetaField[] $metaFieldAnnotations */
        $metaFieldAnnotations = $this->hydrator->extractSpecAnnotations($this->className, MetaField::class);
        $searchParams = new SearchParams();

        foreach ($criteria as $columnName => $value) {
            $annotation = null;
            if (isset($fieldAnnotations[$columnName])) {
                $annotation = $fieldAnnotations[$columnName];
            } else if (isset($metaFieldAnnotations[$columnName])) {
                $annotation = $metaFieldAnnotations[$columnName];
            }

            if (is_null($annotation)) {
                $msg = sprintf("field/metafield for column '%s' doesn't exist in %s entity class",
                    $columnName, $this->className);
                throw new InvalidParamsException($msg);
            }

            if ($annotation->name === '_parent') {
                $searchParams->setParent($criteria[$columnName]);
            } else {
                $must[] = array(
                    'match' => array(
                        $annotation->name => $criteria[$columnName],
                    )
                );
            }
        }

        if (is_array($orderBy)) {
            foreach ($orderBy as $columnName => $order) {
                if (isset($fieldAnnotations[$columnName])) {
                    $sort[$fieldAnnotations[$columnName]->name] = ['order' => $order];
                } else if (isset($metaFieldAnnotations[$columnName])) {
                    $sort[$metaFieldAnnotations[$columnName]->name] = ['order' => $order];
                }
            }
        }

        $body['query']['bool']['must'] = $must;

        $searchParams->setIndex($type->getIndex());
        $searchParams->setType($type->getName());
        $searchParams->setBody($body);
        $searchParams->setSize($limit);
        $searchParams->setSort($sort);
        $searchParams->setFrom($offset);

        return $this->queryExecutor->execute($searchParams, $this->className);
    }

    public function getAnnotionReader() {
        return $this->annotationReader;
    }

    public function executeInserts() {
        foreach ($this->queuedInserts as $entity) {
            $type = $this->getEntityType();

            $this->em->getEventManager()->dispatchEvent(
                DoctrineElasticEvents::beforeInsert, new EntityEventArgs($entity)
            );

            $fieldsData = $this->hydrator->extractWithAnnotation($entity, Field::class);
            $metaFieldsData = $this->hydrator->extractWithAnnotation($entity, MetaField::class);
            $mergeParams = [];

            if (array_key_exists('_id', $metaFieldsData) && !empty($metaFieldsData['_id'])) {
                $mergeParams['id'] = $metaFieldsData['_id'];
            }

            if (isset($metaFieldsData['_parent'])) {
                $mergeParams['parent'] = $metaFieldsData['_parent'];
            }

            $this->createTypeIfNotExists($type, $this->className);

            $this->checkConstraints($entity);
            $return = [];

            $inserted = $this->em->getConnection()->insert(
                $type->getIndex(), $type->getName(), $fieldsData, $mergeParams, $return
            );

            if ($inserted) {
                $this->hydrateEntityByResult($entity, $return);
                $this->em->getEventManager()->dispatchEvent(
                    DoctrineElasticEvents::postInsert, new EntityEventArgs($entity)
                );
            } else {
                throw new ElasticOperationException(sprintf(
                    'Unable to complete insert operation: %s', $this->em->getConnection()->getError()
                ));
            }
        }
    }

    /**
     * @param Type $type
     * @param string $className
     * @throws ElasticConstraintException
     */
    private function createTypeIfNotExists(Type $type, $className) {
        foreach ($type->getChildClasses() as $childClass) {
            $this->createTypeIfNotExists($this->getEntityType($childClass), $childClass);
        }

        $indexName = $type->getIndex();
        $typeName = $type->getName();

        if (!$this->em->getConnection()->typeExists($indexName, $typeName)) {
            $propertiesMapping = [];
            /** @var Field[] $ESFields */
            $ESFields = $this->hydrator->extractSpecAnnotations($className, Field::class);

            foreach ($ESFields as $ESField) {
                if ($ESField instanceof Field) {
                    $propertiesMapping[$ESField->name] = ['type' => $ESField->type];

                    foreach ($ESField->getArrayCopy() as $prop => $propValue) {
                        if ($ESField->type == 'nested' && ($prop == 'boost' || $prop == 'index')) {
                            continue;
                        }
                        if (!is_null($propValue) && $prop != 'name') {
                            $propertiesMapping[$ESField->name][$prop] = $propValue;
                        }
                    }
                }
            }

            $mappings = array(
                $typeName => array(
                    'properties' => $propertiesMapping
                )
            );

            if ($type->getParentClass()) {
                $refParentClass = new \ReflectionClass($type->getParentClass());
                /** @var Type $parentType */
                $parentType = $this->getAnnotionReader()->getClassAnnotation($refParentClass, Type::class);

                if ($parentType->getIndex() != $type->getIndex()) {
                    throw new ElasticConstraintException('Child and parent types have different indices. ');
                }

                $mappings[$typeName]['_parent'] = ['type' => $parentType->getName()];
            }

            if (!$this->em->getConnection()->indexExists($indexName)) {
                $created = $this->em->getConnection()->createIndex($indexName, $mappings);
            } else {
                $created = $this->em->getConnection()->createType($indexName, $typeName, $mappings);
            }

            if (!$created) {
                throw new ElasticOperationException(
                    'Unable to create index or type: ' . $this->em->getConnection()->getError()
                );
            }
        }
    }

    /**
     * Check contraints values for entity, if exists
     *
     * @param object $entity
     * @throws ElasticConstraintException
     */
    private function checkConstraints($entity) {
        /** @var Constraint[] $constraintAnnotations */
        $constraintAnnotations = $this->hydrator->extractSpecAnnotations($this->className, Constraint::class);

        foreach ($constraintAnnotations as $property => $annotation) {
            $value = $this->hydrator->extract($entity, $property);

            switch ($annotation->type) {
                case Constraint::UNIQUE_VALUE:
                    if (!is_null($value)) {
                        $element = $this->load([$property => $value]);

                        if (boolval($element)) {
                            $messageError = sprintf(
                                "Unique field %s already has a document with value '%s'", $property, $value
                            );
                            
                            if ($annotation->options) {
                                $messageError = array_key_exists('message', $annotation->options) ? $annotation->options['message'] : $messageError;
                            }

                            throw new ElasticConstraintException($messageError);
                        }
                    }

                    break;
                case Constraint::MATCH_LENGTH:
                case Constraint::MAX_LENGTH:
                case Constraint::MIN_LENGTH:
                    if (isset($annotation->options['value'])) {
                        $baseLength = intval($annotation->options['value']);

                        if (is_array($value) || is_string($value)) {
                            $length = is_array($value) ? count($value) : strlen($value);
                            $operator = Constraint::$operators[$annotation->type];

                            if (!eval(sprintf('%s %s %s', $length, $operator, $baseLength))) {
                                throw new ElasticConstraintException(sprintf(
                                    "Length for column %s must be %s %s. Current length: %s",
                                    $property, $operator, $baseLength, $length
                                ));
                            }
                        }
                    }

                    break;
            }
        }
    }

    public function load(array $criteria, $limit = null, array $orderBy = null) {
        $results = $this->loadAll($criteria, $orderBy, $limit);

        return count($results) ? $results[0] : null;
    }

    /**
     * @param null|string $className
     * @return Type
     * @throws MappingException
     */
    public function getEntityType($className = null) {
        if (boolval($className)) {
            $refClass = new \ReflectionClass($className);
        } else {
            $refClass = $this->getReflectionClass();
        }

        $type = $this->annotationReader->getClassAnnotation($refClass, Type::class);

        if ($type instanceof Type) {
            return $type;
        } else {
            throw new MappingException(sprintf('Unable to get Type Mapping of %s entity', $className));
        }
    }

    private function hydrateEntityByResult($entity, array $searchResult) {
        if (isset($searchResult['_source'])) {
            $searchResult = array_merge($searchResult, $searchResult['_source']);
        }

        $this->hydrator->hydrate($entity, $searchResult);

        return $entity;
    }

    public function update($entity) {
        $type = $this->getEntityType();
        $dataUpdate = $this->hydrator->extractWithAnnotation($entity, Field::class);
        $_id = $this->hydrator->extract($entity, '_id');
        $return = [];

        $updated = $this->em->getConnection()->update(
            $type->getIndex(), $type->getName(), $_id, $dataUpdate, [], $return
        );

        if ($updated) {
            $this->hydrateEntityByResult($entity, $return);
        } else {
            throw new ElasticOperationException(sprintf(
                'Unable to complete update operation: %s ', $this->em->getConnection()->getError()
            ));
        }
    }

    public function addInsert($entity) {
        $oid = spl_object_hash($entity);
        $this->queuedInserts[$oid] = $entity;
    }

    public function loadById(array $_idArray, $entity = null) {
        $type = $this->getEntityType();

        if (is_object($entity) && get_class($entity) != $this->className) {
            throw new \InvalidArgumentException('You can only get an element by _id with its properly persister');
        }

        $id = isset($_idArray['_id']) ? $_idArray['_id'] : reset($_idArray);

        $documentData = $this->em->getConnection()->get($type->getIndex(), $type->getName(), $id);

        if ($documentData) {
            $entity = is_object($entity) ? $entity : new $this->className;
            $this->hydrateEntityByResult($entity, $documentData);

            return $entity;
        }

        return null;
    }

    public function delete($entity) {
        $type = $this->getEntityType();
        $return = [];
        $_id = $this->hydrator->extract($entity, '_id');

        $deletion = $this->em->getConnection()->delete(
            $type->getIndex(), $type->getName(), $_id, [], $return
        );

        if ($deletion) {
            return true;
        } else {
            throw new ElasticOperationException(sprintf(
                'Unable to complete delete operation: %s', $this->em->getConnection()->getError()
            ));
        }
    }
}
