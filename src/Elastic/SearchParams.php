<?php

namespace DoctrineElastic\Elastic;


class SearchParams implements SearchParamsInterface {

    protected $index;

    protected $type;

    protected $body;

    protected $size;

    /**
     * @return string
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * @param string $index
     * @return SearchParamsInterface
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
     * @return SearchParamsInterface
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
     * @return SearchParamsInterface
     */
    public function setBody(array $body) {
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
}