<?php

namespace DoctrineElastic\Tests;

use Doctrine\Common\Annotations\AnnotationRegistry;
use DoctrineElastic\Connection\ElasticConnection;
use DoctrineElastic\ElasticEntityManager;
use PHPUnit\Framework\TestCase;

/**
 * Base class for PhpUnit test classes
 *
 * @author Ands
 */
abstract class BaseTestCaseTest extends TestCase {

    private static $esVersionPrinted = false;

    /** @var ElasticEntityManager */
    protected static $_elasticEntityManager;

    protected $defaultHost = 'http://localhost:9200';

    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        self::$_elasticEntityManager = $this->_getEntityManager();

        if (!self::$esVersionPrinted) {
            print sprintf(
                "\nElasticsearch version set up to %s\n",
                self::$_elasticEntityManager->getConnection()->getElasticsearchVersion()
            );

            self::$esVersionPrinted = true;
        }
    }

    public function testClientConnect() {
        $this->assertTrue($this->hasElasticConnection(), "There's no elasticsearch connection");
    }

    /**
     * Creates default local Entity Manager for DoctrineElastic
     * @return ElasticEntityManager
     * @throws \Exception
     */
    protected function _getEntityManager() {
        if (!self::$_elasticEntityManager instanceof ElasticEntityManager) {
            AnnotationRegistry::registerFile(getcwd() . '/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
            AnnotationRegistry::registerFile(getcwd() . '/src/Mapping/Driver/ElasticAnnotations.php');

            $esRootVersion = getenv('ELASTICSEARCH_ROOT_VERSION');

            $host = getenv("ELASTICSEARCH{$esRootVersion}_TESTS_HTTP");

            if (!$host) {
                trigger_error(
                    'Unable to get environment vars for elasticsearch tests. '
                    . 'Setting default host as ' . $this->defaultHost
                );

                $host = $this->defaultHost;
            }

            print "\nElasticsearch host set up to $host";

            $conn = new ElasticConnection($host, $esRootVersion);

            self::$_elasticEntityManager = new ElasticEntityManager($conn);
        }

        return self::$_elasticEntityManager;
    }

    protected function hasElasticConnection() {
        return $this->_getEntityManager()->getConnection()->hasConnection();
    }
}
