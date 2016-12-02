<?php

namespace DoctrineElastic\Query\Walker;


class OperatorsMap {

    const LIKE = 'like';
    const UNLIKE = 'unlike';
    const GTE = '>=';
    const GT = '>';
    const LTE = '<=';
    const LT = '<';
    const EQ = '=';
    const NEQ = '<>';

    public static $mapElastic = array(
        self::GT => 'gt',
        self::GTE => 'gte',
        self::LT => 'lt',
        self::LTE => 'lte',
        self::LIKE => 'like',
        self::UNLIKE => 'unlike',
        self::EQ => 'eq',
        self::NEQ => 'neq'
    );

    /**
     * @param string $operator
     * @return bool
     */
    public static function isRangeOperator($operator) {
        return in_array($operator, [self::GTE, self::GT, self::LT, self::LTE]);
    }
}