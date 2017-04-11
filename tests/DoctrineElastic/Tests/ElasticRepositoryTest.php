<?php

namespace DoctrineElastic\Tests;

use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Entity\FooType;

/**
 * Test class for ElasticEntityManager get Repository
 *
 * @see ElasticEntityManager::getRepository()
 * @author Ands
 */
class ElasticRepositoryTest extends ElasticEntityManagerTest {

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
    public function testInsertion() {
        parent::testInsertion();
    }

    /** @depends testInsertion */
    public function testFindOneBy() {
        if ($customFieldValue = self::$_fooType->getCustomField()) {
            try {
                $repository = $this->_getEntityManager()->getRepository(FooType::class);
                $fooEntity = $repository->findOneBy(['customField' => $customFieldValue]);

                $this->assertInstanceOf(FooType::class, $fooEntity,
                    "Repository::findOneBy failed to find previously inserted test data - fieldValue: $customFieldValue");
            } catch (\Exception $ex) {
                $this->assertTrue(
                    false, 'Repository::findOneBy failed to execute: ' . $ex->getMessage()
                );
            }
        }

        $this->assertNotNull(self::$_fooType->_id, "_id was not provided to test Repository::findOneBy method");
    }

    /** @depends testInsertion */
    public function testFindBy() {
        if ($customFieldValue = self::$_fooType->getCustomField()) {
            try {
                $repository = $this->_getEntityManager()->getRepository(FooType::class);
                /** @var FooType[] $fooEntities */
                $fooEntities = $repository->findBy(
                    ['customField' => $customFieldValue], ['customField' => 'ASC']
                );

                $this->assertNotEmpty($fooEntities,
                    "Repository::findBy failed to find previously inserted test data: $customFieldValue");

                foreach ($fooEntities as $fooEntity) {
                    $this->assertInstanceOf(
                        FooType::class, $fooEntity, 
                        'Repository::findBy brought entities different of target: ' . FooType::class
                    );

                    $this->assertEquals(
                        $fooEntity->getCustomField(), $customFieldValue,
                        sprintf('Repository::findBy failed to filter entities from conditions, '
                            . 'actual %s value field, expected %s. ', $fooEntity->getCustomField(), $customFieldValue)
                    );
                }
            } catch (\Exception $ex) {
                $this->assertTrue(
                    false, 'Repository::findOneBy failed to execute: ' . $ex->getMessage()
                );
            }
        }
    }

    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();
    }
}