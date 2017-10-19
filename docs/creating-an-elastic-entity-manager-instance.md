# Creating an ElasticEntityManager Instance
#

DoctrineElastic\ElasticEntityManager is the EntityManager equivalent for this Elasticsearch Doctrine Adaptation. 

The following code is what we need to do:
```php
<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use DoctrineElastic\ElasticEntityManager;

// Notice that these paths must to be valid in your project
AnnotationRegistry::registerFile(getcwd() . '/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
AnnotationRegistry::registerFile(getcwd() . '/vendor/andsalves/doctrine-elastic/src/Mapping/Driver/ElasticAnnotations.php');

$connection = new \DoctrineElastic\Connection\ElasticConnection([
    'http' => 'http://localhost:9200' // You can provide from some configuration or env var
]);

$elasticEntityManager = new ElasticEntityManager($connection);

```





        
