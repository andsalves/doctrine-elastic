<?php

namespace DoctrineElastic\Mapping;

use Doctrine\ORM\Mapping\Annotation;

/**
 * Represents a field with some constraint limitation
 * Exemple: Constraint(type="UniqueValue")
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Constraint implements Annotation
{
    const UNIQUE_VALUE = 'UniqueValue';

    const MATCH_LENGTH = 'MatchLength';
    const MAX_LENGTH = 'MaxLength';
    const MIN_LENGTH = 'MinLength';

    public static $operators = [
        Constraint::MATCH_LENGTH => '==',
        Constraint::MAX_LENGTH => '<=',
        Constraint::MIN_LENGTH => '=>',
    ];

    public $type;

    public $options;
}
