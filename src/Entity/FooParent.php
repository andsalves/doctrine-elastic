<?php

namespace DoctrineElastic\Entity;

use Doctrine\ORM\Mapping as ORM;
use DoctrineElastic\Mapping as ElasticORM;

/**
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 *
 * @ElasticORM\Type(name="foo_parent", index="foo_family", childClasses={"DoctrineElastic\Entity\FooChild"})
 * @ORM\Entity
 */
class FooParent {

    /**
     * @var string
     * @ElasticORM\MetaField(name="_id")
     * @ORM\Column(name="_id", type="integer")
     * @ORM\Id
     */
    public $_id;

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
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     * @return FooParent
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getAge() {
        return $this->age;
    }

    /**
     * @param string $age
     * @return FooParent
     */
    public function setAge($age) {
        $this->age = $age;
        return $this;
    }


}