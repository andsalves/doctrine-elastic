<?php

namespace DoctrineElastic\Entity;

use Doctrine\ORM\Mapping as ORM;
use DoctrineElastic\Mapping as ElasticORM;

/**
 * Elastic type representation, like a relational table managed by Doctrine
 * @author Ands
 *
 *
 * @ElasticORM\Type(name="foo_type", index="foo_index")
 * @ORM\Table(name="foo_table")
 * @ORM\Entity
 */
class FooType {

    /**
     * @var string
     * @ElasticORM\MetaField(name="_id")
     */
    public $_id;

    /**
     * @var int
     *
     * @ORM\Column(name="custom_identifier", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ElasticORM\Field(name="custom_identifier", type="integer")
     */
    private $customIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="custom_field", type="string")
     * @ElasticORM\Field(name="custom_field", type="string")
     */
    private $customField;

    /**
     * @return int
     */
    public function getCustomIdentifier() {
        return $this->customIdentifier;
    }

    /**
     * @param int $customIdentifier
     * @return FooType
     */
    public function setCustomIdentifier($customIdentifier) {
        $this->customIdentifier = $customIdentifier;
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
}

