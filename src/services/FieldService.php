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
     * @return array
     * @throws \yii\base\ExitException
     */
    public function getAllFieldValuesFromFieldType(string $fieldType)
    {
        $ret = [];

        $fields = $this->getFieldsByFieldType($fieldType);

        foreach ($fields as $field) {
            $content = $this->getContentByColumnName($field['handle']);

            if (!empty($content)) {
                $ret[] = $content;
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
            ])
            ->from(['{{%fields}} fields'])
            ->where([
                'type' => $fieldType,
            ])
            ->orderBy(['fields.handle' => SORT_ASC]);

        return $query->all();
    }

    private function getContentByColumnName(string $columnName)
    {
        // default field prefix, it could be that the field has another prefix
        // maybe support other prefixes in future versions
        $prefix = Craft::$app->content->fieldColumnPrefix;

        $fullName = $prefix . $columnName;

        $query = (new Query())
            ->select([
                $fullName,
            ])
            ->from(['{{%content}} content'])
            ->where($fullName . ' IS NOT NULL AND ' . $fullName . ' != \'[]\'');

        $result = $query->all();

        if (empty($result)) {
            return $result;
        }

        return array_column($result, $fullName);
    }
}
