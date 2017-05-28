<?php

namespace DoctrineElastic\Connection;

/**
 * Interface for elastic connection implementation
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
interface ElasticConnectionInterface {

    /**
     * @param string $index
     * @param array|null $mappings
     * @param array|null $settings
     * @param array|null $aliases
     * @param array|null $return
     * @return bool
     */
    public function createIndex(
        $index, array $mappings = null, array $settings = null, array $aliases = null, array &$return = null
    );

    /**
     * @param string $index
     * @param array|null $return
     * @return bool
     */
    public function deleteIndex($index, array &$return = null);

    /**
     * @param string $index
     * @param string $type
     * @param array $mappings
     * @param array|null $return
     * @return bool
     */
    public function createType($index, $type, array $mappings = [], array &$return = null);

    /**
     * @param string $index
     * @param string $type
     * @param array $body
     * @param array $mergeParams
     * @param array $return
     * @return bool
     */
    public function insert($index, $type, array $body, array $mergeParams = [], array &$return = null);

    /**
     * @param string $index
     * @param string $type
     * @param string $_id
     * @param array $body
     * @param array $mergeParams
     * @param array|null $return
     * @return bool
     */
    public function update($index, $type, $_id, array $body = [], array $mergeParams = [], array &$return = null);

    /**
     * @param string $index
     * @param string $type
     * @param string $_id
     * @param array $mergeParams
     * @param array|null $return
     * @return bool
     */
    public function delete($index, $type, $_id, array $mergeParams = [], array &$return = null);

    public function updateWhere($index, $type, array $where);

    public function deleteWhere($index, $type, array $where);

    /**
     * @param string $index
     * @param string $type
     * @return array
     */
    public function typeExists($index, $type);

    /**
     * @param string $index
     * @return bool
     */
    public function indexExists($index);

    /**
     * @param string $index
     * @param string $type
     * @param array $body
     * @param array $mergeParams
     * @return array
     */
    public function search($index, $type, array $body = [], array $mergeParams = []);

    /**
     * @param string $index
     * @param string $type
     * @param string $_id
     * @param array $mergeParams
     * @param array|null $return
     * @return array|null
     */
    public function get($index, $type, $_id, array $mergeParams = [], array &$return = null);

    /**
     * @return bool
     */
    public function hasConnection();
}