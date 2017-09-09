<?php

namespace DoctrineElastic\DoctrineMock;


use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;

class GetConfigurationEntityManager extends EntityManagerDecorator {

    /** @var Configuration */
    protected $mockedConfiguration;

    public function getConfiguration() {
        if (is_null($this->mockedConfiguration)) {
            $this->mockedConfiguration = new Configuration();
        }

        return $this->mockedConfiguration;
    }

}