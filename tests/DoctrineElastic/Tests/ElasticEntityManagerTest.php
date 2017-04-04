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
 * @see ElasticEntityManager
 * @author Ands
 */
class ElasticEntityManagerTest extends BaseTestCaseTest {

    public static $_testId;

    /** @var ElasticEntityManager */
    protected $_em;

    public function setUp() {
        parent::setUp();
        $this->_em = $this->_getEntityManager();
    }

    public function testGetInstance() {
        $this->assertInstanceOf(ElasticEntityManager::class, $this->_em,
            sprintf("Failed to get an instance of %s", ElasticEntityManager::class));
    }

    public function testGetConnection() {
        $this->assertInstanceOf(ElasticConnection::class, $this->_em->getConnection(),
            sprintf("ElasticEntityManager::getConnection failed to return a %s instance", ElasticConnection::class));
    }

    public function testGetRepository() {
        $this->assertInstanceOf(EntityRepository::class, $this->_em->getRepository(FooType::class),
            sprintf("ElasticEntityManager::getRepository failed to return a %s instance", EntityRepository::class));
    }

    public function testGetUnitOfWork() {
        $this->assertInstanceOf(ElasticUnitOfWork::class, $this->_em->getUnitOfWork(),
            sprintf("ElasticEntityManager::getUnitOfWork failed to return a %s instance", ElasticUnitOfWork::class));
    }

    public function testGetExpressionBuilder() {
        $this->assertInstanceOf(Expr::class, $this->_em->getExpressionBuilder(),
            sprintf("ElasticEntityManager::getExpressionBuilder failed to return a %s instance", Expr::class));
    }

    public function testCreateQuery() {
        $this->assertInstanceOf(ElasticQuery::class, $this->_em->createQuery(),
            sprintf("ElasticEntityManager::createQuery failed to return a %s instance", ElasticQuery::class));
    }

    public function testCreateQueryBuilder() {
        $this->assertInstanceOf(ElasticQueryBuilder::class, $this->_em->createQueryBuilder(),
            sprintf("ElasticEntityManager::createQueryBuilder failed to return a % instance", ElasticQueryBuilder::class));
    }

    public function testGetEventManager() {
        $this->assertInstanceOf(EventManager::class, $this->_em->getEventManager(),
            sprintf("ElasticEntityManager::getEventManager failed to return a %s instance", EventManager::class));
    }

    public function testGetConfiguration() {
        $this->assertInstanceOf(Configuration::class, $this->_em->getConfiguration(),
            sprintf("ElasticEntityManager::getConfiguration failed to return a %s instance", Configuration::class));
    }

    public function testInsertion() {
        $testEntity = new FooType();
        $testEntity->setCustomIdentifier(rand(1, 1000));
        $testEntity->setCustomField('Test Value');

        try {
            $this->_getEntityManager()->persist($testEntity);
            $this->_getEntityManager()->flush($testEntity);
        } catch (\Exception $e) {
            $this->assertFalse(true, 'ElasticEntityManager failed to insert data: ' . $e->getMessage());
        }

        $this->assertFalse(
            is_null($testEntity->_id),
            'ElasticEntityManager::persist failed to hydrate entity with _id metafield'
        );

        self::$_testId = $testEntity->_id;
    }

    public function testFind() {
        $testEntity = new FooType();

        if (!is_null(self::$_testId)) {
            $id = self::$_testId;
            $search = $this->_em->find(get_class($testEntity), $testEntity->_id);

            $this->assertTrue($search instanceof FooType,
                "ElasticEntityManager::find failed to find previously inserted test data - _id: $id");
        } else {
            trigger_error("_id was not provided to test ElasticEntityManager::find method");
        }
    }

    public function testGetReference() {
        if (!is_null(self::$_testId)) {
            $id = self::$_testId;
            $search = $this->_em->getReference(FooType::class, $id);

            $this->assertTrue($search instanceof FooType,
                "ElasticEntityManager::getReference failed to find previously inserted test data - _id: $id");
        } else {
            trigger_error("_id was not provided to test ElasticEntityManager::getReference method");
        }
    }

    public function testRemove() {
        if (!is_null(self::$_testId)) {
            $id = self::$_testId;
            $testEntity = $this->_em->find(FooType::class, $id);

            try {
                $this->_getEntityManager()->remove($testEntity);
                $this->_getEntityManager()->flush($testEntity);
            } catch (\Exception $e) {
                $this->assertFalse(true, 'ElasticEntityManager failed to remove entity: ' . $e->getMessage());
            }

            $remainEntity = $this->_em->find(FooType::class, $id);

            $this->assertFalse($remainEntity instanceof FooType, sprintf(
                'Entity of type %s and _id %s still exists after remove', get_class($testEntity), self::$_testId
            ));
        } else {
            trigger_error("_id was not provided to test ElasticEntityManager::remove method");
        }
    }
}