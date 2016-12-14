<?php

namespace DoctrineElastic\Elastic;

/**
 * Entity class for representation of Elasticsearch api search query
 *
 * @author
 */
class SearchParams {

    /* extrapolating elastic max results, ignoring elastic default as 10 */
    const DEFAULT_LIMIT_RESULTS = 1000000000;

    protected $index;

    protected $type;

    protected $body = [];

    protected $from = 0;

    protected $size = self::DEFAULT_LIMIT_RESULTS;

    protected $sort = [];

    /**
     * @return string
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * @param string $index
     * @return SearchParams
     */
    public function setIndex($index) {
        $this->index = $index;
        return $this;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     * @return SearchParams
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * @param array $body
     * @return SearchParams
     */
    public function setBody(array $body = []) {
        $this->body = $body;
        return $this;
    }

    /**
     * @return int
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @param int $size
     * @return SearchParams
     */
    public function setSize($size) {
        $this->size = $size;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSort() {
        return $this->sort;
    }

    /**
     * @param array $sort
     * @return SearchParams
     */
    public function setSort(array $sort = []) {
        $this->sort = $sort;
        return $this;
    }

    /**
     * @return int
     */
    public function getFrom() {
        return $this->from;
    }

    /**
     * @param int $from
     * @return SearchParams
     */
    public function setFrom($from) {
        $this->from = $from;
        return $this;
    }

    public function isValid() {
        return boolval($this->index);
    }
}