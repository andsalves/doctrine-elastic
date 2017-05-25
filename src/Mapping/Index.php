<?php
/**
 * Created by ToOR.
 * Date: 10/05/17
 * Time: 17:17
 * Email: iuribrindeiro@gmail.com
 */

namespace DoctrineElastic\Mapping;


use Doctrine\ORM\Mapping\Annotation;

/**
 * Represents a index for entity
 *
 * @author ToOR
 *
 * @Annotation
 * @Target("CLASS")
 */
class Index implements Annotation
{
    /** @var  string */
    public $name;

    /** @var  string */
    public $translogDurability;

    /** @var  string */
    public $translogSyncInterval;

    /** @var  string */
    public $translogFlushThreshouldSize;

    /** @var  string */
    public $refreshInterval;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Index
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getTranslogDurability()
    {
        return $this->translogDurability;
    }

    /**
     * @param string $translogDurability
     * @return Index
     */
    public function setTranslogDurability($translogDurability)
    {
        $this->translogDurability = $translogDurability;
        return $this;
    }

    /**
     * @return string
     */
    public function getTranslogSyncInterval()
    {
        return $this->translogSyncInterval;
    }

    /**
     * @param string $translogSyncInterval
     * @return Index
     */
    public function setTranslogSyncInterval($translogSyncInterval)
    {
        $this->translogSyncInterval = $translogSyncInterval;
        return $this;
    }

    /**
     * @return string
     */
    public function getTranslogFlushThreshouldSize()
    {
        return $this->translogFlushThreshouldSize;
    }

    /**
     * @param string $translogFlushThreshouldSize
     * @return Index
     */
    public function setTranslogFlushThreshouldSize($translogFlushThreshouldSize)
    {
        $this->translogFlushThreshouldSize = $translogFlushThreshouldSize;
        return $this;
    }

    public function isValid()
    {
        return is_string($this->name);
    }

    public function getErrorMessage() {
        $baseMessage = "'%s' property wasn't set in %s annotation";

        if (!is_string($this->name)) {
            return sprintf($baseMessage, 'name', get_class($this));
        }

        return null;
    }

    public function getArrayCopy() {
        return [
            'name'     => $this->name,
            'settings' => [
                'index' => [
                    'refresh_interval' => $this->refreshInterval,
                    'index.translog.durability' => $this->translogDurability,
                    'index.translog.sync_interval' => $this->translogSyncInterval,
                    'index.translog.flush_threshold_size' => $this->translogFlushThreshouldSize
                ]
            ]
        ];
    }
}