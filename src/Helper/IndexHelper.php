<?php

namespace DoctrineElastic\Helper;

/**
 * @author iuribrindeiro
 */
class IndexHelper {

    public static $invalidIndexCharacters = [' ', '"', '*', '/', '<', '|', ',', '>', '\\', '?', '\''];
    public static $scapedInvalidIndexCharacters = ['\s', '\"', '\*', '\/', '<', '\|', ',', '>', '\\\\', '\?', '\''];

    /**
     * @param string $index
     * @return string
     */
    public static function clearIndex(&$index) {
        $index = mb_strtolower(str_replace(' ', '', $index));
        return $index;
    }

    /**
     * @param string $index
     * @return bool
     */
    public static function indexIsValid($index) {
        if (empty($index)) {
            return false;
        }

        $pattern = '/(' . implode('|', self::$scapedInvalidIndexCharacters) . ')/';

        return !boolval(preg_match($pattern, $index));
    }
}
