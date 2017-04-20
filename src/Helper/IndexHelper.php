<?php

/**
 * Created by ToOR.
 * Date: 20/04/17
 * Time: 15:43
 * Email: iuribrindeiro@gmail.com
 */

namespace DoctrineElastic\Helper;

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