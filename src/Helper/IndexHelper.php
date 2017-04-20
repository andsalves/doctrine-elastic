<?php

namespace DoctrineElastic\Helper;

/**
 * @author iuribrindeiro
 *
 */
class IndexHelper
{
    /**
     * @param $index
     * @return string
     */
    public static function clearIndex(&$index)
    {
        $index = mb_strtolower(str_replace(' ', '', $index));
        return $index;
    }
}
