# Doctrine-Elastic
Custom Doctrine Library for Elasticsearch.

[![Build Status](https://travis-ci.org/andsalves/doctrine-elastic.svg?branch=master)](https://travis-ci.org/andsalves/doctrine-elastic) [![Coverage Status](https://coveralls.io/repos/github/andsalves/doctrine-elastic/badge.svg)](https://coveralls.io/github/andsalves/doctrine-elastic) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/andsalves/doctrine-elastic/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/andsalves/doctrine-elastic/?branch=master)

Last stable release: v1.3.1 (Elasticsearch 2.x or 5.x support)

Tests on Elasticsearch 2.4.1/5.1/5.5 - PHP 5.6/7.0

*This library is not actively maintained anymore*

## Get Started

### Creating a working ElasticEntityManager

Please see https://github.com/andsalves/doctrine-elastic/blob/master/docs/creating-an-elastic-entity-manager-instance.md

### Creating a working DoctrineElastic Entity
Just like Doctrine, we need to set some annotations in our entities, here's an example:
```php
<?php
namespace DoctrineElastic\Entity;

use Doctrine\ORM\Mapping as ORM;
use DoctrineElastic\Mapping as ElasticORM;

/**
 * @author Ands
 *
 * @ElasticORM\Type(name="foo_type", index="foo_index")
 * @ORM\Entity
 */
class FooType {

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ElasticORM\MetaField(name="_id")
     * @ORM\Column(name="_id", type="integer")
     */
    public $_id;

    /**
     * @var int
     *
     * @ElasticORM\Field(name="custom_numeric_field", type="integer")
     */
    private $customNumericField;
    
    /**
     * @var string
     * 
     * Below: Use 'string' for elasticsearch 2.x and 'keyword' for elasticsearch 5.x+
     * @ElasticORM\Field(name="custom_field", type="string")
     */
    private $customField;
    
    /**
     * @var array
     *
     * @ElasticORM\Field(name="custom_nested_field", type="nested")
     */
    private $customNestedField = [];
    
    /**
     * @return int
     */
    public function getCustomNumericField() {
        return $this->customNumericField;
    }
    
    /**
     * @param int $customNumericField
     * @return FooType
     */
    public function setCustomNumericField($customNumericField) {
        $this->customNumericField = $customNumericField;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getCustomField() {
        return $this->customField;
    }
    
    /**
     * @param string $customField
     * @return FooType
     */
    public function setCustomField($customField) {
        $this->customField = $customField;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getCustomNestedField() {
        return $this->customNestedField;
    }
    
    /**
     * @param array $customNestedField
     * @return FooType
     */
    public function setCustomNestedField($customNestedField) {
        $this->customNestedField = $customNestedField;
        return $this;
    }
}
```
This entity represents, in Elasticsearch, a type named 'foo_type', that belongs to an index named 'foo_index'. Note the class annotation @ElasticORM\Type with these definitions. The property annotation @ElasticORM\Field represents a _source field of a document inside the 'foo_type' type. The @ElasticORM\MetaField annotation represents a metafield, like _id. @ElasticORM\MetaField _id is required for an entity, and must be a public property.

Only properties with @ElasticORM\Field annotation will be considered document fields. In elasticsearch, the document column name will be the 'name' property from @ElasticORM\Field annotation from the class property, just like the 'type' annotation property.

### Inserting Documents
With this library, making CRUD operations through ElasticEntityManager is really simple. 
Assuming you have an ElasticEntityManager instance in a variable called $elasticEntityManager:
```php
$newFoo = new DoctrineElastic\Entity\FooType(); // Or wherever be your entity
$newFoo->setCustomNumericField(1234);
$newFoo->setCustomField('Test Value');
$newFoo->setCustomNestedField(['some_value' => 'Some Value', 'whatever' => 'Whatever']);

$elasticEntityManager->persist($newFoo); // Persisting entity...
$elasticEntityManager->flush(); // And flushing... Oh God, just like Doctrine!
```
##### Note 1: 
Index and type will be created automatically, as well as their mappings, if don't exist yet.
##### Note 2: 
By default, mappings for analyzable fields will be not_analyzed (index='not_analyzed'). DoctrineElastic was made to work this way. However, you can change it with 'index' @ElasticORM\Field annotation property, if you prefer default analized fields. e.g. @ElasticORM\Field(name='mad_field', type='string', index='analyzed'). Attention: Search documents with ElasticEntityManager is not guaranteed when you do that, once it isn't always possible to match exact values. 
##### Note 3:
DoctrineElastic does not accept TRANSACTIONS (yet). You will find an available 'beginTransaction' method in ElasticEntityManager, but it does nothing. It is there because ElasticEntityManager implements EntityManagerInterface from Doctrine. That happens with some few other methods. 

##### Note 4:
Just like in Doctrine, after flushing, the entity will have the _id field filled. If you persist an entity with _id field non null, DoctrineElastic will search a doc for update, if it doesn't exist, it's created with the provided _id. 

### Finding Documents
If you know Doctrine, this is very easy and intuitive:
```php
$myFoo = $elasticEntityManager->getRepository(DoctrineElastic\Entity\FooType::class)->findOneBy(['customNumericField' => 1234]);

if (!is_null($myFoo)) {
    print 'Yes, I found it!';
} else {
    print 'Nothing here';
}
```
##### Note 1:
You can use findBy and findAll methods too. 
##### Note 2:
It doesn't matter if index and type exist or not in your Elasticsearch. If not exist, no documents are returned, also no exception is thrown.
##### Note 3:
To search by _id, use $elasticEntityManager::getReference or $elasticEntityManager::find (they are equivalent in ElasticEntityManager).

### Removing Documents
```php
$myFoo = $elasticEntityManager->getRepository(DoctrineElastic\Entity\FooType::class)->findOneBy(['customNumericField' => 1234]);

if (!is_null($myFoo)) {
    // Let's delete this one
    $elasticEntityManager->remove($myFoo);
    $elasticEntityManager->flush(); // Deleted :)
} else {
    print 'Nothing to remove';
}
```
### Using Query Builder
Please see the tests for this feature as a good example: 
https://github.com/andsalves/doctrine-elastic/blob/master/tests/DoctrineElastic/Tests/ElasticEntityManagerTest.php

### Parent-Child Relationship
Please see the tests for this feature as a good example: 
https://github.com/andsalves/doctrine-elastic/blob/master/tests/DoctrineElastic/Tests/ParentChildTest.php

### Application-side Relationships with DoctrineElastic
You can simulate relational databases relationships, with loss of performance, obviously. DoctrineElastic has this feature as an internal feature development, but it is not recommended to be used - if you need complex relationships, you should use a relational database. If you'd really like to use relationships like that with this library, contact me for help.

#
#
###### For questions, please contact me at ands.alves.nunes@gmail.com.
###### Please feel free to open issues or make pull requests. 
#
#
#
#
