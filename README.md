# Doctrine-Elastic
Doctrine Adaptation Library for Elasticsearch.

[![Build Status](https://travis-ci.org/andsalves/doctrine-elastic.svg?branch=master)](https://travis-ci.org/andsalves/doctrine-elastic) [![Coverage Status](https://coveralls.io/repos/github/andsalves/doctrine-elastic/badge.svg)](https://coveralls.io/github/andsalves/doctrine-elastic)

## Get Started
### Create a ElasticEntityManager
We can find about how to create an ElasticEntityManager in docs at https://github.com/andsalves/doctrine-elastic/blob/master/docs/creating-an-elastic-entity-manager-instance.md

### Creating a working DoctrineElastic Entity
Just like Doctrine, we need to set some annotations in our entities, here is a example:
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
This entity represents, in Elasticsearch, a type named 'foo_type', that belongs to an index named 'foo_index'. Note the class annotation @ElasticORM\Type with these informations. The property annotation @ElasticORM\Field represents a field from _source of a document into 'foo_type' type. @ElasticORM\MetaField annotation represents a metafield, like _id. @ElasticORM\MetaField _id is required for an entity, and must be a public property.

Only properties with @ElasticORM\Field annotation will be considered as document fields. In elasticsearch, the document column name will be the 'name' property from @ElasticORM\Field annotation from class property, just like 'type' annotation property.

A more detailed explanation about fields customization and types (just like nested and date types, 'format' annotation property etc), will be available soon. 

### Inserting Documents
Now is very simple to make CRUD operations through ElasticEntityManager. 
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
Index and type will be created automatically, as well as its mappings, if doesn't exist.
##### Note 2: 
By default, mappings for analyzable fields will be not_analyzed (index='not_analyzed'). DoctrineElastic was made to work this way. However, you can change it with 'index' @ElasticORM\Field annotation property, if you prefer default analized fields. e.g. @ElasticORM\Field(name='mad_field', type='string', index='analyzed'). Attention: Search documents with ElasticEntityManager is not guaranteed when you make this, once it isn't possible to match exact values always. 
##### Note 3:
DoctrineElastic does not accept TRANSACTIONS (yet). You will find an available 'beginTransaction' method in ElasticEntityManager, but it doesn't anything. It's there because ElasticEntityManager implements EntityManagerInterface from Doctrine. This happens with some few other methods. 

##### Note 4:
Just like in Doctrine, after flush, the entity will have the _id field filled. If you persist an entity with _id field non null, DoctrineElastic will search a doc for update, if doesn't exist, creates this one with passed _id. 

### Finding Documents
If you really know Doctrine, this is very easy.
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
### Using QueryBuilder
Coming soon...
For emergency, please see the tests for this feature, it's a good example: 
https://github.com/andsalves/doctrine-elastic/blob/master/tests/DoctrineElastic/Tests/ElasticEntityManagerTest.php

### Parent-Child Relationship
Coming soon...
For emergency, please see the tests for this feature, it's a good example: 
https://github.com/andsalves/doctrine-elastic/blob/master/tests/DoctrineElastic/Tests/ParentChildTest.php

### Application-side Relationships with DoctrineElastic
You can simulate relational databases relationships, with loss of performance, obsiously. DoctrineElastic has this feature, but it is not recommended to use. 
A documentation about this is coming soon... For emergency, please contact me. 

### Customizing Results with DoctrineElastic Events
Coming soon...

#
#
###### For documentation missing clarification or any other doubt, please contact me ands.alves.nunes@gmail.com.
###### You can open issues or make pull requests. 
#
#
#
#
