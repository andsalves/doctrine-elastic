<?php

namespace DoctrineElastic\Elastic;

class FieldTypes {

    const STRING = 'string';
    const TEXT = 'text';
    const KEYWORD = 'keyword';
    const LONG = 'long';
    const INTEGER = 'integer';
    const SHORT = 'short';
    const BYTE = 'byte';
    const DOUBLE = 'double';
    const FLOAT = 'float';
    const DATE = 'date';
    const BOOLEAN = 'boolean';
    const BINARY = 'binary';

    const OBJECT = 'object';
    const NESTED = 'nested';

    const GEO_POINT = 'geo_point';
    const GEO_SHAPE = 'geo_shape';

    const IP = 'ip';
    const COMPLETION = 'completion';
    const MURMUR3 = 'murmur3';
    const ATTACHMENT = 'attachment';


    public static $doctrineToElastic = array(
        'string' => self::STRING,
        'text' => self::TEXT,
        'guid' => self::TEXT,
        'binary' => self::BINARY,
        'integer' => self::INTEGER,
        'smallint' => self::INTEGER,
        'bigint' => self::INTEGER,
        'decimal' => self::DOUBLE,
        'float' => self::FLOAT,
        'double' => self::DOUBLE,
        'blob' => self::TEXT,
        'boolean' => self::BOOLEAN,
        'date' => self::DATE,
        'datetime' => self::DATE,
        'datetimetz' => self::DATE,
        'time' => self::DATE,
        'dateinterval' => self::STRING,
        'array' => self::NESTED,
        'simple_array' => self::NESTED,
        'json' => self::NESTED,
        'json_array' => self::NESTED,
        'object' => self::OBJECT,
    );

    public static function doctrineToElastic($doctrineType) {
        if (isset(self::$doctrineToElastic[$doctrineType])) {
            return self::$doctrineToElastic[$doctrineType];
        }

        throw new \InvalidArgumentException("Doctrine type '$doctrineType' was not found for conversion. ");
    }
}