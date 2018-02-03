<?php

namespace DoctrineElastic\Connection;

use DoctrineElastic\Exception\ConnectionException;
use DoctrineElastic\Exception\ElasticOperationException;
use DoctrineElastic\Exception\InvalidIndexNameException;
use DoctrineElastic\Exception\InvalidParamsException;
use DoctrineElastic\Helper\IndexHelper;
use DoctrineElastic\Helper\MappingHelper;
use DoctrineElastic\Http\CurlRequest;
use DoctrineElastic\Traiting\ErrorGetterTrait;

/**
 * Default elastic connection class for general operations
 * Notice that the original elastic result of most of operations can be get by $return param
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class ElasticConnection implements ElasticConnectionInterface {

    use ErrorGetterTrait;

    /** Override default elastic limit size query */
    const DEFAULT_MAX_RESULTS = 10000;

    /** @var CurlRequest */
    protected $curlRequest;

    /** @var float */
    protected $esVersion;

    public function __construct($host, $version = null) {
        $this->curlRequest = new CurlRequest();
        $baseHost = is_array($host) ? reset($host) : $host;

        if (empty($baseHost) || !is_string($baseHost) || !preg_match('/http/', $baseHost)) {
            throw new ConnectionException("Elasticsearch host is invalid. ");
        }

        $this->curlRequest->setBaseUrl($baseHost);

        if (!is_null($version)) {
            $this->esVersion = $version;
        }
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

        $params = [];

        if (is_array($mappings) && !empty($mappings)) {
            $params['mappings'] = MappingHelper::patchMappings($mappings, floor($this->getElasticsearchVersion()));
        }

        if (is_array($settings) && !empty($settings)) {
            $params['settings'] = $settings;
        }

        if (is_array($aliases) && !empty($aliases)) {
            $params['aliases'] = $aliases;
        }

        $response = $this->curlRequest->request($index, $params, 'PUT');
        $return = $response['content'];

        if (isset($return['acknowledged']) && $return['acknowledged']) {
            return $return['acknowledged'];
        }

        $this->setErrorFromElasticReturn($return);

        return false;
    }

    /**
     * @param string $index
     * @param array|null $return
     * @return bool
     * @throws ElasticOperationException
     */
    public function deleteIndex($index, array &$return = null) {
        if (boolval($index) && is_string($index) && !strstr('_all', $index) && $index != '*') {
            $response = $this->curlRequest->request("$index?refresh=true", [], 'DELETE');
            $return = $response['content'];

            if ($response['status'] == 404) {
                throw new ElasticOperationException("Index '$index' doesn't exist so cannot be deleted. ");
            }

            if (isset($return['acknowledged'])) {
                return $return['acknowledged'];
            }
        } else {
            $this->setError("Index name is invalid for deletion. Index name: '$index'");
            return false;
        }

        $this->setErrorFromElasticReturn($return);

        return false;
    }

    /**
     * @param string $index
     * @param string $type
     * @param array $mappings
     * @param array|null $return
     * @return bool
     * @throws ElasticOperationException
     */
    public function createType($index, $type, array $mappings = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            throw new \InvalidArgumentException(sprintf("%s' index does not exists", $index));
        }

        if ($this->typeExists($index, $type)) {
            throw new \InvalidArgumentException(sprintf("Type 's%' already exists on index %s", $type, $index));
        }

        $mappings = MappingHelper::patchMappings($mappings, floor($this->getElasticsearchVersion()));

        $url = "$index/_mapping/$type";
        $response = $this->curlRequest->request($url, $mappings, 'PUT');

        $this->throwExceptionFromResponse($response, "Error creating type '$type' in '$index' index");

        $return = $response['content'];

        if (isset($return['acknowledged'])) {
            return $return['acknowledged'];
        }

        $this->setErrorFromElasticReturn($return);

        return false;
    }

    /**
     * @param string $index
     * @param string $type
     * @param array $body
     * @param array $queryParams
     * @param array|null $return
     * @return bool
     */
    public function insert($index, $type, array $body, array $queryParams = [], array &$return = null) {
        $url = "$index/$type";
        if (isset($body['_id'])) {
            $url .= '/' . $body['_id'];
            unset($body['_id']);
        }

        $url = "$url?" . http_build_query(array_merge(['refresh' => "true"], $queryParams));

        $response = $this->curlRequest->request($url, $body, 'POST');

        $this->throwExceptionFromResponse($response);
        $return = $response['content'];

        if (isset($return['created'])) {
            return $return['created'];
        }

        $this->setErrorFromElasticReturn($return);

        return false;
    }

    /**
     * @param string $index
     * @param string $type
     * @param string $_id
     * @param array $body
     * @param array $queryParams
     * @param array|null $return
     *
     * @return bool
     */
    public function update($index, $type, $_id, array $body = [], array $queryParams = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            return false;
        }

        if (array_key_exists('doc', $body)) {
            $params = $body;
        } else {
            $params = ['doc' => $body];
        }

        $url = "$index/$type/$_id/_update?" . http_build_query(array_merge(['refresh' => 'true'], $queryParams));
        $response = $this->curlRequest->request($url, $params, 'POST');
        $this->throwExceptionFromResponse($response);

        $return = $response['content'];

        if (isset($return['_id'])) {
            return true;
        }

        $this->setErrorFromElasticReturn($return);

        return false;
    }

    /**
     * @param string $index
     * @param string $type
     * @param string $_id
     * @param array $queryParams
     * @param array|null $return
     * @return bool
     */
    public function delete($index, $type, $_id, array $queryParams = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            return false;
        }

        $url = "$index/$type/$_id?" . http_build_query(array_merge(['refresh' => 'true'], $queryParams));
        $response = $this->curlRequest->request($url, [], 'DELETE');
        $this->throwExceptionFromResponse($response);
        $return = $response['content'];

        if (isset($return['found']) && !$return['found']) {
            error_log("Doc with _id '$_id' was not found for delete. Index: '$index', Type: '$type' ");
        }

        if (isset($return['_id'])) {
            return true;
        }

        $this->setErrorFromElasticReturn($return);

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
     * @param array $queryParams
     * @param array|null $return
     * @return array|null
     */
    public function get($index, $type, $_id, array $queryParams = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            return null;
        }

        $url = "$index/$type/$_id";
        $response = $this->curlRequest->request($url, [], 'GET');
        $return = $response['content'];

        if ($response['status'] == 404) {
            return null;
        }

        if (isset($return['found']) && boolval($return['found'])) {
            return $return;
        }

        return null;
    }

    /**
     * Returns the [hits][hits] array from query
     *
     * @param string $index
     * @param string $type
     * @param array $body
     * @param array $queryParams
     * @param array|null $return
     * @return array
     */
    public function search($index, $type, array $body = [], array $queryParams = [], array &$return = null) {
        if (!$this->indexExists($index)) {
            return [];
        }

        $queryParams = array_replace_recursive([
            'size' => self::DEFAULT_MAX_RESULTS
        ], array_filter($queryParams, function ($value) {
            return $value !== null;
        }));

        $body = $this->unsetEmpties($body);

        if (isset($body['query']) && empty($body['query'])) {
            unset($body['query']);
        }

        $url = "$index/$type/_search?" . http_build_query($queryParams);

        $cleanQuery = function ($queryPart, callable $recusiveFn) {
            if (!is_array($queryPart)) {
                return $queryPart;
            }

            foreach ($queryPart as $key => $item) {
                if ($key == 'query' && isset($queryPart['query']['bool'])) {
                    $queryPart['bool'] = $recusiveFn($queryPart['query']['bool'], $recusiveFn);
                    unset($queryPart['query']['bool']);
                }

                if (isset($item['query']['bool'])) {
                    $queryPart[$key]['bool'] = $recusiveFn($item['query']['bool'], $recusiveFn);
                    unset($queryPart[$key]['query']);
                } else if (is_array($item)) {
                    $queryPart[$key] = $recusiveFn($item, $recusiveFn);
                }
            }

            return $queryPart;
        };

        if (isset($body['query'])) {
            foreach ($body['query']['bool'] as $key => $item) {
                $body['query']['bool'][$key] = $cleanQuery($item, $cleanQuery);
            }
        }

        $response = $this->curlRequest->request($url, $body, 'POST');
        $this->throwExceptionFromResponse($response);
        $return = $response['content'];

        if (isset($return['hits']['hits'])) {
            return $return['hits']['hits'];
        }

        return [];
    }

    /**
     * @param $action
     * @param $index
     * @param $type
     * @param array|null $data
     * @return array|bool
     * @throws InvalidParamsException
     */
    public function bulk($action, $index, $type, array $data = null) {
        $bulkData = '';

        if (!in_array($action, ['create', 'index', 'update']) && !is_array($data)) {
            throw new InvalidParamsException(
                "\$data param must be an array for '$action' bulk action"
            );
        }

        switch ($action) {
            case 'create':
            case 'index':
                foreach ($data as $doc) {
                    $actionParams = ['_index' => $index, '_type' => $type];

                    if (isset($doc['_id'])) {
                        $actionParams['_id'] = $doc['_id'];
                        unset($doc['_id']);
                    }

                    $bulkData .= json_encode([$action => $actionParams]) . "\n";
                    $bulkData .= json_encode($doc) . "\n";
                }
                break;
            case 'update':
            case 'delete':
                foreach ($data as $doc) {
                    if (!isset($doc['_id'])) {
                        throw new InvalidParamsException(
                            "_id field must be provided for each item in \$data param for $action action"
                        );
                    }

                    $actionParams = ['_index' => $index, '_type' => $type, '_id' => $doc['_id']];
                    unset($doc['_id']);

                    if ($action == 'update') {
                        if (!array_key_exists('doc', $doc)) {
                            $doc = ['doc' => $doc];
                        }

                        $bulkData .= json_encode([$action => $actionParams]) . "\n";
                        $bulkData .= json_encode($doc) . "\n";
                    }
                }

                break;
            default:
                $bulkActions = ['index', 'create', 'delete', 'update'];

                throw new InvalidParamsException(
                    "Invalid 'action' param provided. Must be one of those: " . implode('|', $bulkActions)
                );
        }

        $response = $this->curlRequest->request("/_bulk", $bulkData, 'POST', [
            'Content-Type: application/x-ndjson; charset=utf-8'
        ]);

        if (isset($response['status']) && $response['status'] == 200) {
            return $response['content'];
        } elseif (isset($response['content'])) {
            return $response['content'];
        }

        return false;
    }

    private function unsetEmpties(array $haystack) {
        $selfFn = __FUNCTION__;

        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->$selfFn($haystack[$key]);
            }

            if (is_array($haystack[$key]) && empty($haystack[$key])) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }

    /**
     * @param string $index
     * @return bool
     */
    public function indexExists($index) {
        $this->validateIndex($index);

        $response = $this->curlRequest->request($index, [], 'HEAD');

        return $response['status'] === 200;
    }

    /**
     * @param string $index
     * @param string $type
     * @return bool
     */
    public function typeExists($index, $type) {
        $response = $this->curlRequest->request("$index/$type", [], 'HEAD');

        return $response['status'] === 200;
    }

    private function throwExceptionFromResponse($response, $appendPrefix = '') {
        if (isset($response['content']['error']['reason'])) {
            if (!empty($appendPrefix)) {
                $appendPrefix .= ': ';
            }

            throw new ElasticOperationException($appendPrefix . $response['content']['error']['reason']);
        }
    }

    public function hasConnection() {
        $response = $this->curlRequest->request('', [], 'HEAD');

        return $response['status'] == 200;
    }

    private function setErrorFromElasticReturn($return) {
        if (isset($return['error']['root_cause'][0]['reason'])) {
            $this->setError($return['error']['root_cause'][0]['reason']);
        } else if (isset($return['error']['reason'])) {
            $this->setError($return['error']['reason']);
        }
    }

    private function validateIndex($index) {
        if (!IndexHelper::indexIsValid($index)) {
            throw new InvalidIndexNameException(sprintf(
                "The index name '$index' is invalid. It must not be empty or contain the following characters: [%s]",
                implode(',', IndexHelper::$invalidIndexCharacters)
            ));
        }
    }

    public function getElasticsearchVersion() {
        if (is_null($this->esVersion)) {
            $response = $this->curlRequest->request('', [], 'GET');

            if (isset($response['content']['version']['number'])) {
                $this->esVersion = floatval($response['content']['version']['number']);
            } else {
                throw new ConnectionException('Unable to fetch elasticsearch version. ');
            }
        }

        return $this->esVersion;
    }
}
