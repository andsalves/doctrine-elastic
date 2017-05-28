<?php

namespace DoctrineElastic\Traiting;

/**
 * Trait ErrorGetterTrait
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
trait ErrorGetterTrait {

    protected $error;

    protected $detail;

    /**
     * @return mixed
     */
    public function getError() {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error) {
        $this->error = $error;
    }

    /**
     * @return mixed
     */
    public function getDetail() {
        return $this->detail;
    }

    /**
     * @param mixed $detail
     */
    public function setDetail($detail) {
        $this->detail = $detail;
    }
}