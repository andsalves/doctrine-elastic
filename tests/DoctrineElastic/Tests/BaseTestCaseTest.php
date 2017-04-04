<?php

namespace DoctrineElastic\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Configuration;
use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Mapping\Driver\ElasticAnnotationDriver;
use Elasticsearch\ClientBuilder;

/**
 * Base class for PhpUnit test classes
 *
 * @author Ands
 */
abstract class BaseTestCaseTest extends \PHPUnit_Framework_TestCase {

    /** @var ElasticEntityManager */
    protected $_em;

    public function setUp() {
        parent::setUp();
        $this->_em = $this->_getEntityManager();
    }

    /**
     * Creates default local Entity Manager for DoctrineElastic
     *
     * @return ElasticEntityManager
     */
    protected function _getEntityManager() {
        if ($this->_em instanceof ElasticEntityManager) {
            return $this->_em;
        }

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

        $elastic = $this->_getElasticClient();

        return new ElasticEntityManager($ormConfig, $elastic, new EventManager());
    }

    /**
     * Creates default local elastic Client
     *
     * @return \Elasticsearch\Client
     */
    protected function _getElasticClient() {
        $hosts = array(
            0 => 'http://localhost:9200',
            1 => 'http://localhost:9200',
        );

        $logger = ClientBuilder::defaultLogger('data/tests/logs/log.txt');

        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->setRetries(0)
            ->setHandler(ClientBuilder::singleHandler())
            ->setLogger($logger)
            ->build();

        return $client;
    }
}