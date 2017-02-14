<?php

namespace DoctrineElastic\Mapping;

use Doctrine\ORM\Mapping\Annotation;

/**
 * Represents a type for entity, with name and index
 *
 * Advice: Values of type name and index can be dynamic by not define one of them, or both,
 * as long as you call Type::setDefaultName and/or Type::setDefaultIndex
 * before your query
 *
 * @author Ands
 *
 * @Annotation
 * @Target("CLASS")
 */
class Type implements Annotation {

    protected static $defaultIndex = null;
    protected static $defaultName = null;

    /** @var string */
    public $name;

    /** @var string */
    public $index;

    /** @return string */
    public function getName() {
        return $this->name ?: self::getDefaultName();
    }

    /**
     * @param string $name
     * @return Type
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /** @return string */
    public function getIndex() {
        return $this->index ?: self::getDefaultIndex();
    }

    /**
     * @param string $index
     * @return Type
     */
    public function setIndex($index) {
        $this->index = $index;
        return $this;
    }

    /** @return null|string */
    public static function getDefaultIndex() {
        return self::$defaultIndex;
    }

    /** @param string $defaultIndex */
    public static function setDefaultIndex($defaultIndex) {
        self::$defaultIndex = strtolower($defaultIndex);
    }

    /** @return null|string */
    public static function getDefaultName() {
        return self::$defaultName;
    }

    /** @param null|string $defaultType */
    public static function setDefaultName($defaultType) {
        self::$defaultName = $defaultType;
    }

    public function isValid() {
        return is_string($this->getIndex()) && is_string($this->getName());
    }

    public function getErrorMessage() {
        $baseMessage = "'%s' property wasn't set in %s annotation";

        if (!is_string($this->index)) {
            return sprintf($baseMessage, 'index', get_class($this));
        }

        if (!is_string($this->name)) {
            return sprintf($baseMessage, 'name', get_class($this));
        }

        return null;
    }
}
