<?php

namespace DoctrineElastic\Event;

use Doctrine\Common\EventArgs;

/**
 * EventArgs relative to Entity operations in DoctrineElastic events
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class EntityEventArgs extends EventArgs
{
    /** @var object */
    protected $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param object $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }


}