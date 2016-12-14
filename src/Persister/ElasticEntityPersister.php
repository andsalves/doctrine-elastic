<?php

namespace DoctrineElastic\Persister;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Elastic\DoctrineElasticEvents;
use DoctrineElastic\Elastic\SearchParams;
use DoctrineElastic\Event\EntityEventArgs;
use DoctrineElastic\Exception\ElasticConstraintException;
use DoctrineElastic\Exception\ElasticOperationException;
use DoctrineElastic\Hydrate\AnnotationEntityHydrator;
use DoctrineElastic\Hydrate\SimpleEntityHydrator;
use DoctrineElastic\Mapping\Field;
use DoctrineElastic\Mapping\MetaField;
use DoctrineElastic\Mapping\Type;
use DoctrineElastic\Service\ElasticSearchService;
use Elasticsearch\Client;

/**
 * Entity Persister for this doctrine elastic extension
 * This class implements some crud operations
 *
 * @author Ands
 */
class ElasticEntityPersister extends AbstractEntityPersister {

    /** @var AnnotationReader */
    private $annotationReader;

    /** @var ElasticSearchService */
    private $elasticSearchService;

    /** @var array */
    protected $queuedInserts = [];

    /** @var AnnotationEntityHydrator */
    private $hydrator;

    public function __construct(ElasticEntityManager $em, ClassMetadata $class, Client $elastic) {
        parent::__construct($em, $class, $elastic);
        $this->annotationReader = new AnnotationReader();
        $this->elasticSearchService = new ElasticSearchService($em->getConnection());
        $this->hydrator = new AnnotationEntityHydrator();
        $this->validateEntity($class->name);
    }

    private function validateEntity($className) {
        $type = $this->annotationReader->getClassAnnotation($this->class->getReflectionClass(), Type::class);

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
            $annotationProperty = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Field::class);

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
            $results[] = $this->hydrator->hydrate($entity, $arrayResult);
        }

        return $results;
    }

    public function getAnnotionReader() {
        return $this->annotationReader;
    }

    public function executeInserts() {
        foreach ($this->queuedInserts as $entity) {
            $type = $this->getEntityType();
            $entityCopy = clone $entity;

            $this->em->getEventManager()->dispatchEvent(
                DoctrineElasticEvents::beforeInsert, new EntityEventArgs($entityCopy)
            );

            $fieldsData = $this->hydrator->extractWithAnnotation($entityCopy, Field::class);
            $metaFieldsData = $this->hydrator->extractWithAnnotation($entityCopy, MetaField::class);
            $mergeParams = [];

            if (isset($metaFieldsData['_id'])) {
                $mergeParams['id'] = $metaFieldsData['_id'];
            }

            $this->createTypeIfNotExists();

            $this->checkIndentityConstraints($entityCopy);
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
                throw new ElasticOperationException(sprintf('Unable to complete update operation, '
                    . 'with the following elastic return: <br><pre>%s</pre>', var_export($return)));
            }
        }
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
            throw new ElasticOperationException(sprintf('Unable to complete update operation, '
                . 'with the following elastic return: <br><pre>%s</pre>', var_export($return)));
        }
    }

    private function createTypeIfNotExists() {
        $type = $this->getEntityType();
        $indexName = $type->getIndex();
        $typeName = $type->getName();
        $className = $this->class->reflClass->name;

        if (!$this->em->getConnection()->typeExists($indexName, $typeName)) {
            $propertiesMapping = [];
            /** @var Field[] $ESFields */
            $ESFields = $this->hydrator->extractSpecAnnotations(new $className(), Field::class);

            foreach ($ESFields as $ESField) {
                if ($ESField instanceof Field) {
                    $propertiesMapping[$ESField->name] = ['type' => $ESField->type];

                    foreach ($ESField->getArrayCopy() as $prop => $propValue) {
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
            $this->hydrateEntityByResult($entity, $documentData);

            return $entity;
        }

        return null;
    }

    private function hydrateEntityByResult($entity, array $searchResult) {
        $hydrator = new SimpleEntityHydrator();

        $hydrator->hydrate($entity, $searchResult);

        if (isset($searchResult['_source'])) {
            $hydrator->hydrate($entity, $searchResult['_source']);
        }
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
            throw new ElasticOperationException(sprintf('Unable to complete update operation, '
                . 'with the following elastic return: <br><pre>%s</pre>', var_export($return)));
        }
    }

    /**
     * Check Identity values for entity, if there are identity fields,
     * check if already exists items with such value (unique constraint verification)
     *
     * @param object $entity
     * @throws ElasticConstraintException
     */
    private function checkIndentityConstraints($entity) {
        $identities = $this->getClassMetadata()->getIdentifierValues($entity);

        foreach ($identities as $property => $value) {
            $element = $this->load([$property => $value]);

            if (boolval($element)) {
                throw new ElasticConstraintException(sprintf("Unique/IDENTITY field %s already has "
                    . "a document with value '%s'", $property, $value));
            }
        }
    }
}