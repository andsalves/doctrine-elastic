<?php

namespace DoctrineElastic\Mapping;

use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Metadata Factory Decorator ElasticClassMetadataFactory
 *
 * @author Ands
 */
abstract class ClassMetadataFactoryDecorator extends AbstractClassMetadataFactory {

    /** @var AbstractClassMetadataFactory */
    protected $wrapped;

    public function __construct(AbstractClassMetadataFactory $classMetadataFactory) {
        $this->wrapped = $classMetadataFactory;
    }

    protected function initialize() {
        $this->wrapped->initialize();
    }

    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName) {
        return $this->wrapped->getFqcnFromAlias($namespaceAlias, $simpleClassName);
    }

    protected function getDriver() {
        return $this->wrapped->getDriver();
    }

    protected function wakeupReflection(ClassMetadata $class, ReflectionService $reflService) {
        $this->wrapped->wakeupReflection($class, $reflService);
    }

    protected function initializeReflection(ClassMetadata $class, ReflectionService $reflService) {
        $this->wrapped->initializeReflection($class, $reflService);
    }

    protected function isEntity(ClassMetadata $class) {
        return $this->wrapped->isEntity($class);
    }

    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents) {
        /** @var ClassMetadataInfo $class */
        $class->generatorType = ClassMetadataInfo::GENERATOR_TYPE_NONE;
        return $this->wrapped->doLoadMetadata($class, $parent, $rootEntityFound, $nonSuperclassParents);
    }

    protected function newClassMetadataInstance($className) {
        return $this->wrapped->newClassMetadataInstance($className);
    }
}