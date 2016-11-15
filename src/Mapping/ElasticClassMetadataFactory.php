<?php

namespace DoctrineElastic\Mapping;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

class ElasticClassMetadataFactory extends ClassMetadataFactoryDecorator {

    public function __construct(EntityManagerInterface $entityManager) {
        $classMetadataFactory = new ClassMetadataFactory();
        $classMetadataFactory->setEntityManager($entityManager);

        parent::__construct($classMetadataFactory);
    }

}