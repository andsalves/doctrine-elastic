<?php

namespace DoctrineElastic\Tests;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use DoctrineElastic\Connection\ElasticConnection;
use DoctrineElastic\Elastic\ElasticQuery;
use DoctrineElastic\Elastic\ElasticQueryBuilder;
use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\ElasticUnitOfWork;
use DoctrineElastic\Entity\FooType;

/**
 * Test class for ElasticEntityManager
 *
 * Tip for this test: In local elasticsearch,
 * foo_type into foo_index must start empty (or nonexistent) and finish empty.
 *
 * @see ElasticEntityManager
 * @author Ands
 */
class ElasticEntityManagerTest extends BaseTestCaseTest {

    /** @var FooType */
    protected static $_fooType;

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        self::clearIndices();
    }

    public function setUp() {
        parent::setUp();
    }

    public function testClientConnect() {
        parent::testClientConnect();
    }

    /** @depends testClientConnect */
    public function testGetInstance() {
        $this->assertInstanceOf(ElasticEntityManager::class, $this->_getEntityManager(),
            sprintf("Failed to get an instance of %s", ElasticEntityManager::class));
    }

    /** @depends testClientConnect */
    public function testGetConnection() {
        $this->assertInstanceOf(ElasticConnection::class, $this->_getEntityManager()->getConnection(),
            sprintf("ElasticEntityManager::getConnection failed to return a %s instance", ElasticConnection::class));
    }

    /** @depends testClientConnect */
    public function testGetRepository() {
        $this->assertInstanceOf(EntityRepository::class, $this->_getEntityManager()->getRepository(FooType::class),
            sprintf("ElasticEntityManager::getRepository failed to return a %s instance", EntityRepository::class));
    }

    /** @depends testClientConnect */
    public function testGetUnitOfWork() {
        $this->assertInstanceOf(ElasticUnitOfWork::class, $this->_getEntityManager()->getUnitOfWork(),
            sprintf("ElasticEntityManager::getUnitOfWork failed to return a %s instance", ElasticUnitOfWork::class));
    }

    /** @depends testClientConnect */
    public function testGetExpressionBuilder() {
        $this->assertInstanceOf(Expr::class, $this->_getEntityManager()->getExpressionBuilder(),
            sprintf("ElasticEntityManager::getExpressionBuilder failed to return a %s instance", Expr::class));
    }

    /** @depends testClientConnect */
    public function testCreateQuery() {
        $this->assertInstanceOf(ElasticQuery::class, $this->_getEntityManager()->createQuery(),
            sprintf("ElasticEntityManager::createQuery failed to return a %s instance", ElasticQuery::class));
    }

    /** @depends testClientConnect */
    public function testGetEventManager() {
        $this->assertInstanceOf(EventManager::class, $this->_getEntityManager()->getEventManager(),
            sprintf("ElasticEntityManager::getEventManager failed to return a %s instance", EventManager::class));
    }

    /** @depends testClientConnect */
    public function testGetConfiguration() {
        $this->assertInstanceOf(Configuration::class, $this->_getEntityManager()->getConfiguration(),
            sprintf("ElasticEntityManager::getConfiguration failed to return a %s instance", Configuration::class));
    }

    /** @depends testClientConnect */
    public function testInsertion() {
        self::$_fooType = new FooType();
        self::$_fooType->setCustomNumericField(rand(1, 1000));
        self::$_fooType->setCustomField('Test Value');
        self::$_fooType->setCustomNestedField(['some_value' => 'Some Value', 'whatever' => 'Whatever']);

        try {
            $this->_getEntityManager()->persist(self::$_fooType);
            $this->_getEntityManager()->flush();
        } catch (\Exception $e) {
            $this->assertTrue(false, 'ElasticEntityManager failed to insert data: ' . $e->getMessage());
        }

        $this->assertNotNull(
            self::$_fooType->_id,
            'ElasticEntityManager failed to insert data or hydrate entity with _id metafield (FooType)'
        );
    }

    /** @depends testInsertion */
    public function testFind() {
        if ($id = self::$_fooType->_id) {
            try {
                $fooEntity = $this->_getEntityManager()->find(FooType::class, $id);

                $this->assertInstanceOf(FooType::class, $fooEntity,
                    "ElasticEntityManager::find failed to find previously inserted test data - _id: $id");
            } catch (\Exception $ex) {
                $this->assertTrue(
                    false, 'ElasticEntityManager::find failed to execute: ' . $ex->getMessage()
                );
            }
        }

        $this->assertNotNull(self::$_fooType->_id, "_id was not provided to test ElasticEntityManager::find method");
    }

    /** @depends testInsertion */
    public function testGetReference() {
        if ($id = self::$_fooType->_id) {
            $search = $this->_getEntityManager()->getReference(FooType::class, $id);

            try {
                $this->assertInstanceOf(FooType::class, $search,
                    "ElasticEntityManager::getReference failed to find previously inserted test data - _id: $id");
            } catch (\Exception $ex) {
                $this->assertTrue(
                    false, 'ElasticEntityManager::getReference failed to execute: ' . $ex->getMessage()
                );
            }
        }

        $this->assertNotNull(self::$_fooType->_id, "_id was not provided to test ElasticEntityManager::getReference method");
    }

    /** @depends testInsertion */
    public function testCreateQueryBuilder() {
        $queryBuilder = $this->_getEntityManager()->createQueryBuilder();

        $this->assertInstanceOf(ElasticQueryBuilder::class, $queryBuilder,
            sprintf("ElasticEntityManager::createQueryBuilder failed to return a % instance", ElasticQueryBuilder::class));

        if (boolval($queryBuilder)) {

        }
    }

    /** @depends testInsertion */
    public function testRemove() {
        if ($id = self::$_fooType->_id) {
            $testEntity = $this->_getEntityManager()->find(FooType::class, $id);

            try {
                $this->_getEntityManager()->remove($testEntity);
                $this->_getEntityManager()->flush($testEntity);
            } catch (\Exception $e) {
                $this->assertFalse(true, 'ElasticEntityManager failed to remove entity: ' . $e->getMessage());
            }

            $remainEntity = $this->_getEntityManager()->find(FooType::class, $id);

            $this->assertNotInstanceOf(FooType::class, $remainEntity, sprintf(
                'Entity of type %s with _id %s still exists after remove', get_class($testEntity), self::$_fooType->_id
            ));
        }

        $this->assertNotNull(self::$_fooType->_id, "_id was not provided to test ElasticEntityManager::remove method");
    }

    protected static function clearIndices() {
        if (self::$_elasticEntityManager->getConnection()->indexExists('foo_index')) {
            self::$_elasticEntityManager->getConnection()->deleteIndex('foo_index');
        }
    }

    public static function tearDownAfterClass() {
        self::clearIndices();
    }
}