<?php

namespace DoctrineElastic\Mapping;

use Doctrine\ORM\Mapping\Annotation;

/**
 * Represents a metafield in elasticsearch.
 * Exemple: _id (required)
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 *
 * @Annotation
 * @Target({"PROPERTY","ANNOTATION"})
 */
class MetaField implements Annotation {

    /** @var string */
    public $name;

    public function isValid() {
        return is_string($this->name);
    }

    public function getErrorMessage() {
        $baseMessage = "'%s' property wasn't set in %s annotation";

        if (!is_string($this->name)) {
            return sprintf($baseMessage, 'name', get_class($this));
        }

        return null;
    }
}