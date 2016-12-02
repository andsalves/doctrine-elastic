<?php

namespace DoctrineElastic\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

class ElasticAnnotationDriver extends AnnotationDriver {

    public function loadMetadataForClass($className, ClassMetadata $metadata) {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadataInfo */
        $idGeneratorType = $metadata->generatorType;

        parent::loadMetadataForClass($className, $metadata);

        $metadata->setIdGeneratorType($idGeneratorType);
    }

}