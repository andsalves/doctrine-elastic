<?php

namespace DoctrineElastic\Tests;

use DoctrineElastic\Connection\ElasticConnection;
use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Exception\ElasticOperationException;
use DoctrineElastic\Exception\InvalidIndexNameException;
use DoctrineElastic\Exception\InvalidParamsException;
use DoctrineElastic\Tests\StaticProvider\TestsDataProvider;

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

    /** @depends testClientConnect */
    public function testCreateIndexInvalidName() {
        $connection = $this->_getEntityManager()->getConnection();

        foreach (TestsDataProvider::$invalidIndexNames as $indexName) {
            try {
                $created = $connection->createIndex($indexName);

                $this->assertFalse($created, printf(
                    "\nIndex with invalid name has returned true (boolean) on creation attempt: %s\n", $indexName
                ));
            } catch (\Exception $exception) {
                $this->assertInstanceOf(
                    InvalidIndexNameException::class, $exception, 'Invalid Exception was thrown during index creation'
                );
            }
        }
    }

    /** @depends testClientConnect */
    public function testInvalidBulkActionRequest() {
        $connection = $this->_getEntityManager()->getConnection();

        try {
            $connection->bulk('invalid_action', 'tests', 'test');

            $this->fail('Bulk request with an invalid action doesn\'t throwed an exception');
        } catch (\Exception $exception) {
            $this->assertInstanceOf(
                InvalidParamsException::class,
                $exception,
                'Throwed exception for invalid bulk action wasn\'t an InvalidParamsException exception'
            );
        }
    }

    /** @depends testClientConnect */
    public function testBulkIndex() {
        $connection = $this->_getEntityManager()->getConnection();

        $responses = $connection->bulk('index', 'tests', 'test', [
            ['field1' => 'A random value'],
            ['field1' => 'Another random value'],
        ]);

        if ($responseIsArray = is_array($responses)) {
            if ($arrayItemsKeyExists = array_key_exists('items', $responses)) {
                foreach ($responses['items'] as $itemResponse) {
                    if ($indexKeyExists = array_key_exists('index', $itemResponse)) {
                        $keyAction = 'index';
                    } elseif ($indexKeyExists = array_key_exists('create', $itemResponse)) {
                        $keyAction = 'create';
                    } else {
                        $this->assertTrue($indexKeyExists, "'index' or 'create' array key in one of items response doesn't exist");
                        continue;
                    }

                    $this->assertTrue(
                        $itemResponse[$keyAction]['status'] == 201,
                        "Index bulk item request wasn't successful"
                    );
                }
            }

            $this->assertTrue($arrayItemsKeyExists, "Bulk array response expected to have 'items' key");
        }

        $this->assertTrue($responseIsArray, sprintf("Bulk response expected to be an array, '%s' returned", gettype($responses)));
    }

    protected static function clearIndices() {
        $deleteStack = array_values(TestsDataProvider::$invalidIndexNames);
        array_push($deleteStack, 'tests');

        foreach ($deleteStack as $indexName) {
            try {
                $usable = !empty($indexName) && $indexName != '*';

                if ($usable && self::$_elasticEntityManager->getConnection()->indexExists($indexName)) {
                    self::$_elasticEntityManager->getConnection()->deleteIndex($indexName);
                }
            } catch (ElasticOperationException $exception) {
                print "\n" . $exception->getMessage() . "\n";
            } catch (InvalidIndexNameException $exception) {
                // It's ok, cannot delete an invalid one
            }
        }
    }

    public static function tearDownAfterClass() {
        self::clearIndices();
    }
}