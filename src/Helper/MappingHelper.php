<?php


namespace DoctrineElastic\Helper;

/**
 * Class MappingHelper
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
class MappingHelper {

    /**
     * Doing some fields mapping modification/adaptation for type/index creation
     *
     * @param array $mappings
     * @param int $elasticRootVersion
     * @return array
     */
    public static function patchMappings(array $mappings, $elasticRootVersion = 2) {
        if (isset($mappings['properties'])) {
            $propertiesMapping = $mappings['properties'];
        } else {
            foreach ($mappings as $key => $mapping) {
                if (is_array($mapping)) {
                    $mappings[$key] = self::patchMappings($mapping, $elasticRootVersion);
                }
            }

            return $mappings;
        }

        foreach ($propertiesMapping as $fieldName => $fieldMap) {
            /**
             * Elasticsearch 5.x support patch ('string' type deprecated)
             */
            if ($fieldMap['type'] == 'string' && $elasticRootVersion >= 5) {
                $propertiesMapping[$fieldName]['type'] = 'keyword';
            }

            if (isset($fieldMap['type']) && in_array($fieldMap['type'], ['string', 'text', 'keyword'])) {
                continue;
            }

            if (isset($propertiesMapping[$fieldName]['index'])) {
                unset($propertiesMapping[$fieldName]['index']);
            }

            if (isset($propertiesMapping[$fieldName]['boost'])) {
                unset($propertiesMapping[$fieldName]['boost']);
            }
        }

        $mappings['properties'] = $propertiesMapping;

        return $mappings;
    }

}