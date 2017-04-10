<?php

namespace DoctrineElastic\Tests;

use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Entity\FooChild;
use DoctrineElastic\Entity\FooParent;

/**
 * Test class for child-parent types relation feature
 *
 *
 * @see ElasticEntityManager
 * @author Ands
 */
class ParentChildTest extends BaseTestCaseTest {

    /** @var FooParent */
    private static $_fooParent;

    /** @var FooChild */
    private static $_fooChild;

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);

        if ($this->_getEntityManager()->getConnection()->indexExists('foo_family')) {
            $this->_getEntityManager()->getConnection()->deleteIndex('foo_family');
        }
    }

    public function setUp() {
        parent::setUp();
    }

    public function testClientConnect() {
        parent::testClientConnect();
    }

    /** @depends testClientConnect */
    public function testInsertParent() {
        try {
            self::$_fooParent = new FooParent();
            self::$_fooParent->setName('Foo Adam');
            self::$_fooParent->setAge(92);

            $this->_getEntityManager()->persist(self::$_fooParent);
            $this->_getEntityManager()->flush(self::$_fooParent);
        } catch (\Exception $ex) {
            $this->assertTrue(false, 'ElasticEntityManager failed to insert data: ' . $ex->getMessage());
        }

        $this->assertNotNull(
            self::$_fooParent->_id,
            'ElasticEntityManager failed to insert data or hydrate entity with _id metafield (FooParent)'
        );
    }

    /** @depends testInsertParent */
    public function testInsertChild() {
        try {
            self::$_fooChild = new FooChild();
            self::$_fooChild->setName('Abel');
            self::$_fooChild->setAge(70);
            self::$_fooChild->_parent = self::$_fooParent->_id;

            $this->_getEntityManager()->persist(self::$_fooChild);
            $this->_getEntityManager()->flush(self::$_fooChild);
        } catch (\Exception $ex) {
            $this->assertTrue(false, 'ElasticEntityManager failed to insert data: ' . $ex->getMessage());
        }

        $this->assertNotNull(
            self::$_fooParent->_id,
            'ElasticEntityManager failed to insert data or hydrate entity with _id metafield (FooChild)'
        );
    }

    /** @depends testInsertChild */
    public function testFindChildFromParent_using_FindOneBy() {
        try {
            $fooChild = $this->_getEntityManager()->getRepository(FooChild::class)->findOneBy(array(
                '_parent' => self::$_fooParent->_id
            ));

            $this->assertNotNull(
                $fooChild,
                'ElasticEntityManager failed to insert data or hydrate entity with _id metafield '
                . '(find FooChild with findOneBy FooParent _id)'
            );

            $noexistentFooChild = $this->_getEntityManager()->getRepository(FooChild::class)->findOneBy(array(
                '_parent' => '123456789012345'
            ));

            $this->assertNull(
                $noexistentFooChild,
                'Searching one child by noexistent parent _id have been found a result! '
                . '(find FooChild with findOneBy noexistent FooParent _id)'
            );
        } catch (\Exception $ex) {
            $this->assertTrue(false, 'ElasticEntityManager failed to search: ' . $ex->getMessage());
        }
    }
}