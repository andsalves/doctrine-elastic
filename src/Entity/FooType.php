<?php

namespace DoctrineElastic\Entity;

use Doctrine\ORM\Mapping as ORM;
use DoctrineElastic\Mapping as ElasticORM;

/**
 * Elastic type representation, like a relational table managed by Doctrine
 *
 * @author Ands
 *
 * @ElasticORM\Type(name="foo_type", index="foo_index")
 * @ORM\Entity
 */
class FooType {

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ElasticORM\MetaField(name="_id")
     * @ORM\Column(name="_id", type="integer")
     */
    public $_id;

    /**
     * @var int
     *
     * @ElasticORM\Field(name="custom_numeric_field", type="integer")
     */
    private $customNumericField;

    /**
     * @var string
     *
     * @ElasticORM\Field(name="custom_field", type="string")
     */
    private $customField;

    /**
     * @var array
     *
     * @ElasticORM\Field(name="custom_nested_field", type="nested")
     */
    private $customNestedField = [];

    /**
     * @return int
     */
    public function getCustomNumericField() {
        return $this->customNumericField;
    }

    /**
     * @param int $customNumericField
     * @return FooType
     */
    public function setCustomNumericField($customNumericField) {
        $this->customNumericField = $customNumericField;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomField() {
        return $this->customField;
    }

    /**
     * @param string $customField
     * @return FooType
     */
    public function setCustomField($customField) {
        $this->customField = $customField;
        return $this;
    }

    /**
     * @return array
     */
    public function getCustomNestedField() {
        return $this->customNestedField;
    }

    /**
     * @param array $customNestedField
     * @return FooType
     */
    public function setCustomNestedField($customNestedField) {
        $this->customNestedField = $customNestedField;
        return $this;
    }


}

