<?php

namespace DoctrineElastic\Connection;

interface ElasticConnectionInterface {

    public function createIndex($index, array $mappings = [], array $settings = [], array $aliases = []);

    public function deleteIndex($index);

    public function createType($index, $type, array $mappings = []);

    public function insert($index, $type, array $body);

    public function update($index, $type, $_id);

    public function delete($index, $type, $_id);

    public function updateWhere($index, $type, array $where);

    public function deleteWhere($index, $type, array $where);
}