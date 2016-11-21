<?php

namespace DoctrineElastic\Mapping;

use Doctrine\ORM\Mapping\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY","ANNOTATION"})
 */
class Field implements Annotation {

    /** @var string */
    public $name;

    /** @var string */
    public $type = 'text';

    /** @var string */
    public $analyzer;

    /** @var string */
    public $search_analyzer;

    /** @var int */
    public $boost = 1;

    /** @var int */
    public $ignore_above;

    /** @var string */
    public $format;

    /** @var string */
    public $index = 'not_analyzed';

    /** @var mixed */
    public $null_value;

    public function isValid() {
        return is_string($this->name);
    }

    public function getErrorMessage() {
        $baseMessage = "'%s' property wasn't set in %s Annotation";

        if (!is_string($this->name)) {
            return sprintf($baseMessage, 'name', self::class);
        }

        return null;
    }

    public function getArrayCopy() {
        $attrs = get_class_vars(self::class);
        $values = [];

        foreach ($attrs as $name => $value) {
            $values[$name] = $this->$name;
        }

        return $values;
    }
}