# Creating an ElasticEntityManager Instance
#

DoctrineElastic\ElasticEntityManager is the EntityManager equivalent for this Elasticsearch Doctrine Adaptation. 
To create an instance of it, we need set up some configurations, just like EntityManager of Doctrine - Some frameworks modules make this job for us, using their specific configs. 
#
The following code is what we need to do:
 
```php
<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Configuration;
use DoctrineElastic\ElasticEntityManager;
use DoctrineElastic\Mapping\Driver\ElasticAnnotationDriver;
use Elasticsearch\ClientBuilder;

$ormConfig = new Configuration();
$driverChain = new MappingDriverChain();
$annotationDriver = new ElasticAnnotationDriver(new AnnotationReader(), []);

AnnotationRegistry::registerFile(getcwd() . '/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
AnnotationRegistry::registerFile(getcwd() . '/vendor/andsalves/doctrine-elastic/src/Mapping/Driver/ElasticAnnotations.php');

$driverChain->addDriver($annotationDriver, 'DoctrineElastic\Entity');

$ormConfig->setMetadataDriverImpl($driverChain);
$ormConfig->setProxyDir('data');
$ormConfig->setProxyNamespace('DoctrineORMModule\Proxy');
$ormConfig->setAutoGenerateProxyClasses(true);
$ormConfig->setEntityNamespaces(['DoctrineElastic\Entity']);

$logger = ClientBuilder::defaultLogger('data/tests/logs/log.txt');

$clientClient = ClientBuilder::create()
    ->setHosts(array(
        0 => 'http://localhost:9200',
        1 => 'http://localhost:9200',
    ))
    ->setRetries(0)
    ->setHandler(ClientBuilder::singleHandler())
    ->setLogger($logger)
    ->build();

$elasticEntityManager = new ElasticEntityManager($ormConfig, $clientClient, new EventManager());

```

> ### A Little Explanation
Well, I know, could be more simple. But our ElasticEntityManager was based on Doctrine EntityManager, and all these dependencies and configs were, at first, necessary. So, the implementations are very similar (This is the way we create a Doctrine EntityManager without having a module to do that, like for Zend Framework). 

The main difference here is an Elastic Client instance we have to create. 
Attempt that this code is only a example, obviously you will create a Factory for ElasticEntityManager, right? And another for Elastic Client, I recommend. 

More details about this code refers more to the Doctrine itself. 

##### DoctrineElastic next versions tends to have less Doctrine dependence, and ElasticEntityManager will be quite simple to create. 








        