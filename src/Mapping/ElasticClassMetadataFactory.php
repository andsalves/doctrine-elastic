<?php

namespace DoctrineElastic\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

/**
 * Metadata factory for this elastic doctrine extension
 *
 * @author Ands
 */
class ElasticClassMetadataFactory extends ClassMetadataFactoryDecorator {

    public function __construct(EntityManagerInterface $entityManager) {
        $classMetadataFactory = new ClassMetadataFactory();
        $classMetadataFactory->setEntityManager($entityManager);

        parent::__construct($classMetadataFactory);
    }

}