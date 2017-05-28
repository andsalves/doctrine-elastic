<?php

namespace DoctrineElastic\Tests;

use Doctrine\Common\Annotations\AnnotationRegistry;
use DoctrineElastic\Connection\ElasticConnection;
use DoctrineElastic\ElasticEntityManager;

/**
 * Base class for PhpUnit test classes
 *
 * @author Ands
 */
abstract class BaseTestCaseTest extends \PHPUnit_Framework_TestCase {

    private static $esVersionPrinted = false;

    /** @var ElasticEntityManager */
    protected static $_elasticEntityManager;

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

            $esRootVersion = strval(getenv('ELASTICSEARCH_ROOT_VERSION'));

            $hosts = array(
                getenv("ELASTICSEARCH{$esRootVersion}_TESTS_HTTP"),
                getenv("ELASTICSEARCH{$esRootVersion}_TESTS_HTTPS")
            );

            if (is_null($hosts[0])) {
                throw new \Exception('Unable to get environment vars for elasticsearch tests. ');
            }

            $conn = new ElasticConnection($hosts);

            self::$_elasticEntityManager = new ElasticEntityManager($conn);
        }

        return self::$_elasticEntityManager;
    }

    protected function hasElasticConnection() {
        return $this->_getEntityManager()->getConnection()->hasConnection();
    }
}
