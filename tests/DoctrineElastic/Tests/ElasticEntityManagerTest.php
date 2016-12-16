<?php

namespace DoctrineElastic\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityRepository;
use DoctrineElastic\Connection\ElasticConnection;
use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Entity\FooType;
use DoctrineElastic\Mapping\Driver\ElasticAnnotationDriver;
use Elasticsearch\ClientBuilder;

/**
 * Test class ElasticEntityManager
 *
 * @see ElasticEntityManager
 * @author Ands
 */
class ElasticEntityManagerTest extends \PHPUnit_Framework_TestCase {

    /** @var ElasticEntityManager */
    private $entityManager;

    public function setUp() {
        $this->entityManager = $this->getEntityManager();
    }

    public function testGetConnection() {
        $this->assertInstanceOf(ElasticConnection::class, $this->entityManager->getConnection());
    }

    public function testGetRepository() {
        $this->assertInstanceOf(EntityRepository::class, $this->entityManager->getRepository(FooType::class));
    }

    private function getEntityManager() {
        $ormConfig = new Configuration();
        $driverChain = new MappingDriverChain();
        $annotationDriver = new ElasticAnnotationDriver(new AnnotationReader(), []);

        AnnotationRegistry::registerFile(__DIR__ . '/../../../../../doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
        AnnotationRegistry::registerFile(__DIR__ . '/../../../src/Mapping/Driver/ElasticAnnotations.php');

        $driverChain->addDriver($annotationDriver, 'DoctrineElastic\Entity');

        $ormConfig->setMetadataDriverImpl($driverChain);
        $ormConfig->setProxyDir('data');
        $ormConfig->setProxyNamespace('DoctrineORMModule\Proxy');
        $ormConfig->setAutoGenerateProxyClasses(true);
        $ormConfig->setEntityNamespaces(['DoctrineElastic\Entity']);

        $elastic = $this->getElasticClient();

        return new ElasticEntityManager($ormConfig, $elastic, new EventManager());
    }

    private function getElasticClient() {
        $hosts = array(
            0 => 'http://localhost:9200',
            1 => 'http://localhost:9200',
        );

        $logger = ClientBuilder::defaultLogger('data/logs/log.txt');

        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->setRetries(0)
            ->setHandler(ClientBuilder::singleHandler())
            ->setLogger($logger)
            ->build();

        return $client;
    }

}