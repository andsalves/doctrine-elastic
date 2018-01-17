<?php

namespace DoctrineElastic\Hydrate;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\Reflection\ReflectionPropertiesGetter;

/**
 * Common Entity Hydrator
 * Uses ReflectionPropertiesGetter from Doctrine
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class SimpleEntityHydrator implements SimpleHydratorInterface
{
    /**
     * @var ReflectionPropertiesGetter
     */
    protected $reflectionPropertiesGetter;

    public function __construct()
    {
        $this->reflectionPropertiesGetter = new ReflectionPropertiesGetter(new RuntimeReflectionService());
    }

    /**
     * @param object $entity
     * @param array $data
     * @return object
     */
    public function hydrate($entity, array $data)
    {
        $classProperties = $this->reflectionPropertiesGetter->getProperties(get_class($entity));

        foreach ($classProperties as $prop) {
            $prop->setAccessible(true);

            if (array_key_exists($prop->name, $data)) {
                $prop->setValue($entity, $data[$prop->name]);
            } else {
                $name = self::decamelizeString($prop->name);

                if (array_key_exists($name, $data)) {
                    $prop->setValue($entity, $data[$name]);
                }
            }
        }

        if (method_exists($entity, 'exchangeArray')) {
            $entity->exchangeArray($data);
        }

        if (method_exists($entity, 'populate')) {
            $entity->populate($data);
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @param string|array $fieldOrFields
     * @return array|mixed
     */
    public function extract($entity, $fieldOrFields = null)
    {
        $filterFields = null;
        $data = [];
        /** @var \ReflectionProperty[] $classProperties */
        $classProperties = $this->reflectionPropertiesGetter->getProperties(get_class($entity));

        if (!is_array($fieldOrFields) && is_string($fieldOrFields)) {
            $filterFields = [$fieldOrFields];
        }

        foreach ($classProperties as $prop) {
            if (is_array($filterFields)) {
                $filtered = in_array($prop->name, $filterFields);
                $filtered |= in_array(self::decamelizeString($prop->name), $filterFields);

                if (!$filtered) {
                    continue;
                }
            }

            $prop->setAccessible(true);
            $value = $prop->getValue($entity);

            $decamelName = self::decamelizeString($prop->name);

            if ($decamelName == $fieldOrFields || $prop->name == $fieldOrFields || $prop->name == self::camelizeString($fieldOrFields)) {
                return $value;
            }

            $data[$decamelName] = $value;
        }

        if (empty($data) && is_string($fieldOrFields)) {
            return null;
        }

        return $data;
    }

    public static function decamelizeString($string)
    {
        if (is_string($string) && !empty($string)) {
            $prefix = '';
            if (substr($string, 0, 1) == '_') {
                $prefix = '_';
            }

            return $prefix . ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $string)), '_');
        }

        return $string;
    }

    public static function camelizeString($string)
    {
        if (is_string($string) && !empty($string)) {
            if (substr($string, 0, 1) == '_') {
                $string = substr($string, 1);
            }

            $words = str_replace('_', ' ', $string);
            $ucWords = ucwords($words);

            return lcfirst(str_replace(' ', '', $ucWords));
        }

        return $string;
    }
}
