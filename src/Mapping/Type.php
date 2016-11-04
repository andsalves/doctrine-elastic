<?php

namespace DoctrineElastic\Mapping;

use Doctrine\ORM\Mapping\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Type implements Annotation {

    /** @var string */
    public $name;

    /** @var string */
    public $index;

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Type
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * @param string $index
     * @return Type
     */
    public function setIndex($index) {
        $this->index = $index;
        return $this;
    }



    public function isValid() {
        return is_string($this->index) && is_string($this->name);
    }

    public function getErrorMessage() {
        $baseMessage = "'%s' property wasn't set in %s Annotation";

        if (!is_string($this->index)) {
            return sprintf($baseMessage, 'index', self::class);
        }

        if (!is_string($this->name)) {
            return sprintf($baseMessage, 'name', self::class);
        }

        return null;
    }
}