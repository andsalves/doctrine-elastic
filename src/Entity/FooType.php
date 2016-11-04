<?php

namespace DoctrineElastic\Entity;

use Doctrine\ORM\Mapping as ORM;
use DoctrineElastic\Mapping as ElasticORM;

/**
 * Elastic type representation, as a relational table managed by Doctrine
 * @author Ands
 *
 *
 * @ElasticORM\Type(name="crm", index="contas")
 * @ORM\Table(name="foo_table")
 * @ORM\Entity
 */
class FooType {

    /**
     * @var int
     *
     * @ORM\Column(name="id_conta", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $customIdentifier;

    /**
     * @var string
     *
     * @ORM\Column(name="nome_conta", type="string", length=100, nullable=false)
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

