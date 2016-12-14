<?php

namespace DoctrineElastic\Elastic;

/**
 * This class represents types and maps for types common to elastic
 *
 * @author Ands
 */
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


    public static $types = array(
        self::STRING,
        self::TEXT,
        self::KEYWORD,
        self::LONG,
        self::INTEGER,
        self::SHORT,
        self::BYTE,
        self::DOUBLE,
        self::FLOAT,
        self::DATE,
        self::BOOLEAN,
        self::BINARY,
        self::OBJECT,
        self::NESTED,
        self::GEO_POINT,
        self::GEO_SHAPE,
        self::IP,
        self::COMPLETION,
        self::MURMUR3,
        self::ATTACHMENT
    );
}