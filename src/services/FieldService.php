<?php
/**
 * Safedelete plugin for Craft CMS 3.x
 *
 * Delete elements without breaking relations
 *
 * @link      https://goldinteractive.ch
 * @copyright Copyright (c) 2020 Goldinteractive
 */

namespace goldinteractive\safedelete\services;

use craft\db\Query;

use Craft;
use craft\base\Component;

/**
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 */
class FieldService extends Component
{
    /**
     * Get all field values from all fields with the given field type
     *
     * @param string $fieldType
     * @param string $filterValue the value the contents field_myfield column should have
     * @param bool   $fuzzySearch
     * @return array
     */
    public function getAllFieldValuesFromFieldType(string $fieldType, string $filterValue = '', $fuzzySearch = false)
    {
        $ret = [];

        $fields = $this->getFieldsByFieldType($fieldType);

        foreach ($fields as $field) {
            $columnName = $field['columnSuffix'] ? $field['handle'] .'_'. $field['columnSuffix'] : $field['handle'];
            $content = $this->getContentByColumnName($columnName, $filterValue, $fuzzySearch);

            if (!empty($content)) {
                $ret[] = [
                    'field'   => $field,
                    'content' => $content,
                ];
            }
        }

        return $ret;
    }

    /**
     * @param string $fieldType
     * @return array
     */
    private function getFieldsByFieldType(string $fieldType)
    {
        $query = (new Query())
            ->select([
                'id',
                'handle',
                'context',
                'type',
                'columnSuffix'
            ])
            ->from(['{{%fields}} fields'])
            ->where([
                'type' => $fieldType,
            ])
            ->orderBy(['fields.handle' => SORT_ASC]);

        return $query->all();
    }

    private function getContentByColumnName(string $columnName, string $filterValue = '', bool $fuzzySearch = false)
    {
        // default field prefix, it could be that the field has another prefix
        // maybe support other prefixes in future versions
        $prefix = Craft::$app->content->fieldColumnPrefix;

        $fullName = $prefix . $columnName;

        $query = (new Query())
            ->select([
                'elementId',
                'siteId',
                $fullName,
            ])
            ->from(['{{%content}} content'])
            ->where($fullName . ' IS NOT NULL AND ' . $fullName . ' != \'[]\'');

        if (!empty($filterValue)) {
            if ($fuzzySearch) {
                $query->andWhere($fullName . ' LIKE ' . $filterValue);
            } else {
                $query->andWhere($fullName . ' = ' . $filterValue);
            }

        }

        $results = $query->all();

        if (empty($results)) {
            return $results;
        }

        // rename the custom field name to just "field"
        return array_map(function ($result) use ($fullName) {
            return [
                'elementId' => $result['elementId'],
                'siteId'    => $result['siteId'],
                'field'     => $result[$fullName],
            ];
        }, $results);
    }
}
