<?php

namespace DoctrineElastic\Connection;

use Elasticsearch\Client;

/**
 * Default elastic connection class for general operations
 * Notice that the original elastic result of most of operations can be get by $return param
 *
 * @author Ands
 */
class ElasticConnection implements ElasticConnectionInterface {

    /** Override default elastic limit size query */
    const DEFAULT_MAX_RESULTS = 10000;

    /** @var Client */
    protected $elastic;

    public function __construct(Client $elastic) {
        $this->elastic = $elastic;
    }

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
    ) {
        if ($this->indexExists($index)) {
            throw new \InvalidArgumentException(sprintf("'%s' index already exists", $index));
        }

        $params = array(
            'index' => $index,
            'update_all_types' => true,
            'body' => []
        );

        if (boolval($mappings)) {
            foreach ($mappings as $typeName => $mapping) {
                $properties = $mapping['properties'];

                foreach ($properties as $fieldName => $fieldMap) {
                    if (isset($fieldMap['type']) && in_array($fieldMap['type'], ['string', 'text', 'keyword'])) {
                        continue;
                    }

                    if (isset($mappings[$typeName]['properties'][$fieldName]['index'])) {
                        unset($mappings[$typeName]['properties'][$fieldName]['index']);
                    }

                    if (isset($mappings[$typeName]['properties'][$fieldName]['boost'])) {
                        unset($mappings[$typeName]['properties'][$fieldName]['boost']);
                    }
                }
            }
            $params['body']['mappings'] = $mappings;
        }

        if (boolval($settings)) {
            $params['body']['settings'] = $settings;
        }

        $return = $this->elastic->indices()->create($params);

        if (isset($return['acknowledged'])) {
            return $return['acknowledged'];
        }

        return false;
    }

    /**
     * @param string $index
     * @param array|null $return
     * @return bool
     */
    public function deleteIndex($index, array &$return = null) {
        if (!$this->indexExists($index)) {
            throw new \InvalidArgumentException(sprintf("'%s' index does not exists", $index));
        }

        if (is_string($index) && !strstr('_all', $index) && !strstr('*', $index)) {
            $return = $this->elastic->indices()->delete(['index' => $index]);

            if (isset($return['acknowledged'])) {
                return $return['acknowledged'];
            }
        }

        return false;
    }

    /**
     * @param string $index
     * @param string $type
     * @param array $mappings
     * @param array|null $return
     * @return bool
     */
    public function createType($index, $type, array $mappings = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            throw new \InvalidArgumentException(sprintf("%s' index does not exists", $index));
        }

        if ($this->typeExists($index, $type)) {
            throw new \InvalidArgumentException(sprintf("Type 's%' already exists on index %s", $type, $index));
        }

        $return = $this->elastic->indices()->putMapping(array(
            'index' => $index,
            'type' => $type,
            'update_all_types' => true,
            'body' => $mappings
        ));

        if (isset($return['acknowledged'])) {
            return $return['acknowledged'];
        }

        return false;
    }

    /**
     * @param string $index
     * @param string $type
     * @param array $body
     * @param array $mergeParams
     * @param array|null $return
     * @return bool
     */
    public function insert($index, $type, array $body, array $mergeParams = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            trigger_error("$index index does not exists at insert attempt");
            return false;
        }

        if (!$this->typeExists($index, $type)) {
            trigger_error("$type type does not exists at insert attempt");
            return false;
        }

        $defaultParams = array(
            'index' => $index,
            'type' => $type,
            'op_type' => 'index',
            'timestamp' => time(),
            'refresh' => "true",
            'body' => $body
        );

        $params = array_merge_recursive($defaultParams, $mergeParams);

        $return = $this->elastic->index($params);

        if (isset($return['created'])) {
            return $return['created'];
        }

        return false;
    }

    /**
     * @param string $index
     * @param string $type
     * @param string $_id
     * @param array $body
     * @param array $mergeParams
     * @param array|null $return
     *
     * @return bool
     */
    public function update($index, $type, $_id, array $body = [], array $mergeParams = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            return false;
        }

        $defaultParams = array(
            'id' => $_id,
            'index' => $index,
            'type' => $type,
            'refresh' => "true",
            'retry_on_conflict' => 4,
            'body' => array(
                'doc' => $body
            )
        );

        $params = array_merge_recursive($defaultParams, $mergeParams);

        $return = $this->elastic->update($params);

        if (isset($return['_id'])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $index
     * @param string $type
     * @param string $_id
     * @param array $mergeParams
     * @param array|null $return
     * @return bool
     */
    public function delete($index, $type, $_id, array $mergeParams = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            return false;
        }

        $defaultParams = array(
            'id' => $_id,
            'index' => $index,
            'type' => $type,
            'refresh' => "true"
        );

        $params = array_merge_recursive($defaultParams, $mergeParams);

        $return = $this->elastic->delete($params);

        if (isset($return['found']) && isset($return['_shards']['successful'])) {
            return boolval($return['_shards']['successful']);
        }

        return false;
    }

    public function updateWhere($index, $type, array $where, array &$return = null) {
        // TODO
    }

    public function deleteWhere($index, $type, array $where, array &$return = null) {
        // TODO
    }

    /**
     *
     * @param string $index
     * @param string $type
     * @param string $_id
     * @param array $mergeParams
     * @param array|null $return
     * @return array|null
     */
    public function get($index, $type, $_id, array $mergeParams = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            return null;
        }

        $defaultParams = array(
            'id' => $_id,
            'index' => $index,
            'type' => $type,
            'refresh' => "true",
            '_source' => true,
            '_source_exclude' => []
        );

        $params = array_merge_recursive($defaultParams, $mergeParams);
        $existsParams = array_filter($params, function ($key) {
            return in_array($key, ['id', 'index', 'type', 'refresh']);
        }, ARRAY_FILTER_USE_KEY);

        if ($this->elastic->exists($existsParams)) {
            $return = $this->elastic->get($params);

            if (isset($return['found']) && $return['found']) {
                return $return;
            }
        }

        return null;
    }

    /**
     * Returns the [hits][hits] array from query
     *
     * @param string $index
     * @param string $type
     * @param array $body
     * @param array $mergeParams
     * @param array|null $return
     * @return array
     */
    public function search($index, $type, array $body = [], array $mergeParams = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            return [];
        }

        $defaultParams = array(
            'index' => $index,
            'type' => $type,
            '_source' => true,
            'request_cache' => false,
            'size' => self::DEFAULT_MAX_RESULTS,
            'body' => $body
        );

        $params = array_replace_recursive($defaultParams, $mergeParams);

        $this->unsetEmpties($params['body']);

        if (empty($params['body'])) {
            unset($params['body']);
        }

        $return = $this->elastic->search($params);

        if (isset($return['hits']['hits'])) {
            return $return['hits']['hits'];
        }

        return [];
    }

    private function unsetEmpties(array &$array, array &$parent = null) {
        for ($count = 2; $count > 0; $count--) {
            foreach ($array as $key => $item) {
                if (is_array($item) && empty($item)) {
                    unset($array[$key]);

                    if (is_array($parent)) {
                        $this->unsetEmpties($parent);
                    }
                } else if (is_array($item)) {
                    $this->unsetEmpties($array[$key], $array);
                }
            }
        }
    }

    /**
     * @param string $index
     * @return bool
     */
    public function indexExists($index) {
        return $this->elastic->indices()->exists(['index' => $index]);
    }

    /**
     * @param string $index
     * @param string $type
     * @return bool
     */
    public function typeExists($index, $type) {
        $return = $this->elastic->indices()->existsType(array(
            'index' => $index,
            'type' => $type,
            'ignore_unavailable' => true
        ));

        return boolval($return);
    }

    /**
     * @return Client
     */
    public function getElasticClient() {
        return $this->elastic;
    }
}
