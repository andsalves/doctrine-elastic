<?php

namespace DoctrineElastic\Entity;

use Doctrine\ORM\Mapping as ORM;
use DoctrineElastic\Mapping as ElasticORM;

/**
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 *
 * @ElasticORM\Type(name="foo_child", index="foo_family", parentClass="DoctrineElastic\Entity\FooParent")
 * @ORM\Entity
 */
class FooChild
{
    /**
     * @var string
     * @ElasticORM\MetaField(name="_id")
     * @ORM\Column(name="_id", type="integer")
     * @ORM\Id
     */
    public $_id;

    /**
     * @var string
     * @ElasticORM\MetaField(name="_parent")
     *
     */
    public $_parent;

    /**
     * @var string
     * @ElasticORM\Field(name="name", type="string")
     *
     */
    protected $name;

    /**
     * @var string
     * @ElasticORM\Field(name="age", type="integer")
     *
     */
    protected $age;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return FooChild
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * @param string $age
     * @return FooChild
     */
    public function setAge($age)
    {
        $this->age = $age;
        return $this;
    }
}