<?php

namespace DoctrineElastic\Tests;

use DoctrineElastic\Connection\ElasticConnection;
use DoctrineElastic\ElasticEntityManager;

/**
 * Test class to test ElasticConnection
 *
 * @see ElasticConnection
 * @author Ands
 */
class ElasticConnectionTest extends BaseTestCaseTest {

    public function setUp() {
        parent::setUp();
    }

    public function testClientConnect() {
        parent::testClientConnect();
    }

    /** @depends testClientConnect */
    public function testGetInstance() {
        $connection = $this->_getEntityManager()->getConnection();

        $this->assertInstanceOf(
            ElasticConnection::class, $connection,
            sprintf("Failed to get an instance of %s", ElasticEntityManager::class)
        );
    }
}