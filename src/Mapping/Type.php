<?php

namespace DoctrineElastic\Mapping;

use Doctrine\ORM\Mapping\Annotation;
use DoctrineElastic\Helper\IndexHelper;

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

    /** @var string */
    public $parentClass;

    /** @var array */
    public $childClasses = [];

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
        IndexHelper::clearIndex($defaultIndex);
        self::$defaultIndex = $defaultIndex;
    }

    /** @return null|string */
    public static function getDefaultName() {
        return self::$defaultName;
    }

    /** @param null|string $defaultType */
    public static function setDefaultName($defaultType) {
        self::$defaultName = $defaultType;
    }

    /**
     * @return string
     */
    public function getParentClass() {
        return $this->parentClass;
    }

    /**
     * @param string $parentClass
     * @return Type
     */
    public function setParentClass($parentClass) {
        $this->parentClass = $parentClass;
        return $this;
    }

    /**
     * @return array
     */
    public function getChildClasses() {
        return $this->childClasses;
    }

    /**
     * @param array $childClasses
     * @return Type
     */
    public function setChildClasses(array $childClasses) {
        $this->childClasses = $childClasses;
        return $this;
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
