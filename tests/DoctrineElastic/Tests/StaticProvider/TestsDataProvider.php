<?php

namespace DoctrineElastic\Tests\StaticProvider;

use DoctrineElastic\Tests\ElasticConnectionTest;

/**
 * This class provides a few of parameters to use in tests
 *
 * @author Andsalves
 */
class TestsDataProvider {

    /**
     * indices list to test invalid index creation
     * @see ElasticConnectionTest::testCreateIndexInvalidName()
     */
    public static $invalidIndexNames = [
        'special_character_name_1' => 'with space',
        'special_character_name_2' => '"double_quotes',
        'special_character_name_3' => 'asterisk**',
        'special_character_name_5' => 'bar//',
        'special_character_name_6' => 'inverse_bar\\',
        'special_character_name_7' => 'lt<',
        'special_character_name_8' => 'gt>',
        'special_character_name_9' => 'coma,asd',
        'special_character_name_10' => 'interrogation??',
        'empty_name' => '',
    ];
}