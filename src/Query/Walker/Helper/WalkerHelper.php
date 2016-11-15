<?php

namespace DoctrineElastic\Query\Walker\Helper;

use Doctrine\ORM\Mapping\Column;
use DoctrineElastic\Elastic\ElasticQuery;
use DoctrineElastic\Elastic\InvalidOperatorException;
use DoctrineElastic\Query\Walker\OperatorsMap;

class WalkerHelper {

    /**
     * @param array $subBody
     * @param array $body
     * @param string $toStatement
     */
    public function addSubQueryStatement(array $subBody, array &$body, $toStatement = 'must') {
        if (isset($body['query']['bool'][$toStatement])) {
            $body['query']['bool'][$toStatement][] = $subBody;
        } else {
            $tempBody = array(
                'query' => array(
                    'bool' => array(
                        $toStatement => [$subBody]
                    )
                )
            );

            $body = array_merge_recursive($body, $tempBody);
        }
    }

    public function addBodyStatement($field, $operator, $value, array &$body) {
        $bodyTemp = array(
            'query' => array(
                'bool' => array(
                    'must' => [],
                    'must_not' => [],
                    'should' => [],
                    'should_not' => [],
                    'filter' => []
                ),

            )
        );

        if (OperatorsMap::isRangeOperator($operator)) {
            $opStr = OperatorsMap::$mapElastic[$operator];
            $bodyTemp['query']['bool']['filter'][] = array(
                'range' => array(
                    $field => array(
                        $opStr => $value
                    )
                )
            );
        } else {
            switch ($operator) {
                case OperatorsMap::EQ:
                case OperatorsMap::NEQ:
                    if (is_null($value)) {
                        $filterField = ($operator == OperatorsMap::EQ) ? 'missing' : 'exists';
                        $bodyTemp['query']['bool']['filter'][] = array(
                            $filterField => ['field' => $field]
                        );
                    } else {
                        $boolField = 'must' . ($operator == OperatorsMap::EQ ? '' : '_not');
                        $itemSearch = array(
                            'query_string' => array(
                                'default_field' => $field,
                                'default_operator' => 'AND',
                                'query' => $value
                            )
                        );
                        $bodyTemp['query']['bool'][$boolField][] = $itemSearch;
                    }
                    break;
                case OperatorsMap::UNLIKE:
                case OperatorsMap::LIKE:
                    $boolField = 'must' . ($operator == OperatorsMap::LIKE ? '' : '_not');
                    $value = str_replace('%', '*', $value);
                    $itemSearch = array(
                        'query_string' => array(
                            'query' => $value,
                            'default_field' => $field,
                            'default_operator' => 'AND'
                        )
                    );
                    $bodyTemp['query']['bool'][$boolField][] = $itemSearch;
                    break;
                default:
                    throw new InvalidOperatorException("'$operator' operator not allowed. ");
            }
        }

        $body = array_merge_recursive($body, $bodyTemp);
    }

    /**
     * @param $propertyName
     * @param $className
     * @param ElasticQuery $query
     * @return Column
     */
    public function getEntityColumn($propertyName, $className, ElasticQuery $query) {
        $classMetadata = $query->getEntityManager()->getClassMetadata($className);

        $entityPersiter = $query->getEntityManager()->getUnitOfWork()
            ->getEntityPersister($className);
        /** @var Column $type */
        $column = $entityPersiter->getAnnotionReader()
            ->getPropertyAnnotation($classMetadata->getReflectionProperty($propertyName), Column::class);

        return $column;
    }
}