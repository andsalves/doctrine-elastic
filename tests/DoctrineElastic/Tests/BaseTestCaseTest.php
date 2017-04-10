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
use Elasticsearch\Common\Exceptions\TransportException;

/**
 * Base class for PhpUnit test classes
 *
 * @author Ands
 */
abstract class BaseTestCaseTest extends \PHPUnit_Framework_TestCase {

    /** @var ElasticEntityManager */
    protected static $_elasticEntityManager;

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->hasElasticConnection();
    }

    public function testClientConnect() {
        $this->assertTrue($this->hasElasticConnection(), "There's no elasticsearch connection");
    }

    /**
     * Creates default local Entity Manager for DoctrineElastic
     *
     * @return ElasticEntityManager
     */
    protected function _getEntityManager() {
        if (!self::$_elasticEntityManager instanceof ElasticEntityManager) {
            $ormConfig = new Configuration();
            $driverChain = new MappingDriverChain();
            $annotationDriver = new ElasticAnnotationDriver(new AnnotationReader(), []);

            AnnotationRegistry::registerFile(getcwd() . '/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
            AnnotationRegistry::registerFile(getcwd() . '/src/Mapping/Driver/ElasticAnnotations.php');

            $driverChain->addDriver($annotationDriver, 'DoctrineElastic\Entity');

            $ormConfig->setMetadataDriverImpl($driverChain);
            $ormConfig->setProxyDir('data');
            $ormConfig->setProxyNamespace('DoctrineORMModule\Proxy');
            $ormConfig->setAutoGenerateProxyClasses(true);
            $ormConfig->setEntityNamespaces(['DoctrineElastic\Entity']);

            $elastic = $this->_getElasticClient();

            self::$_elasticEntityManager = new ElasticEntityManager($ormConfig, $elastic, new EventManager());
        }

        return self::$_elasticEntityManager;
    }

    /**
     * Creates default local elastic Client
     *
     * @return \Elasticsearch\Client
     */
    protected function _getElasticClient() {
        if (self::$_elasticEntityManager instanceof ElasticEntityManager) {
            return self::$_elasticEntityManager->getConnection()->getElasticClient();
        }

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

    protected function hasElasticConnection() {
        try {
            $this->_getElasticClient()->cat()->indices();

            return true;
        } catch (TransportException $ex) {
            $this->assertTrue(false, 'Could not connect to elasticsearch: ' . $ex->getMessage());
        } catch (\Exception $ex) {
            $this->assertTrue(false, 'Could not test to get elasticsearch indices: ' . $ex->getMessage());
        }

        return false;
    }
}