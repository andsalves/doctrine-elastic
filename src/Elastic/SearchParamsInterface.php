<?php

namespace DoctrineElastic\Elastic;

/**
 * Interface for SearchParams class
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
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