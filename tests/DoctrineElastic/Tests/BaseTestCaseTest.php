<?php

namespace DoctrineElastic\Tests;

use Doctrine\Common\Annotations\AnnotationRegistry;
use DoctrineElastic\ElasticEntityManager;
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
        self::$_elasticEntityManager = $this->_getEntityManager();

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
            AnnotationRegistry::registerFile(getcwd() . '/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
            AnnotationRegistry::registerFile(getcwd() . '/src/Mapping/Driver/ElasticAnnotations.php');

            $elastic = $this->_getElasticClient();

            self::$_elasticEntityManager = new ElasticEntityManager($elastic);
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
            0 => 'http://213.32.71.136:9200',
            1 => 'http://213.32.71.136:9200',
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
