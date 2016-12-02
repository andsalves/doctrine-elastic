<?php

namespace DoctrineElastic\Elastic;


interface SearchParamsInterface {

    public function getIndex();
    public function setIndex($index);

    public function getType();
    public function setType($type);

    public function getSize();
    public function setSize($size);

    public function getBody();
    public function setBody(array $body);

}