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
use craft\elements\Entry;
use goldinteractive\safedelete\SafeDelete;

use Craft;
use craft\base\Component;

/**
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 */
class SafeDeleteService extends Component
{
    public function getUsagesFor($ids, $type)
    {
        $relations = [];

        foreach ($ids as $id) {
            switch ($type) {
                case 'asset':
                case 'element':
                    $res = $this->getRelationsForElement($id);

                    if (count($res) > 0) {
                        $relations[] = $res;
                    }
                    break;
            }
        }

        return $relations;
    }

    /**
     * Returns an array only with ids which are
     * not referneced and safe to delete.
     *
     * @param $ids
     * @param $type
     * @return array
     */
    public function filterReferencedIds($ids, $type)
    {
        $arrIds = [];
        $arrRet = [];

        $relations = $this->getUsagesFor($ids, $type);

        foreach ($relations as $elements) {
            foreach ($elements as $element) {
                $arrIds[] = $element['sourceElement']->id;
            }
        }

        foreach ($ids as $id) {
            if (!in_array($id, $arrIds)) {
                $arrRet[] = $id;
            }
        }

        return $arrRet;
    }

    protected function getRelationsForElement($id)
    {
        $arrReturn = [];

        $sourceElement = Craft::$app->elements->getElementById($id);

        $results = (new Query())->select('fieldId, sourceId')->from('relations')->where(
            'targetId = :targetId',
            ['targetId' => $id]
        )->all();

        foreach ($results as $relation) {
            $fieldId = $relation['fieldId'];
            $sourceId = $relation['sourceId'];

            $field = Craft::$app->fields->getFieldById($fieldId);
            $element = Craft::$app->elements->getElementById($sourceId);

            if ($element !== null) {
                $elementType = Craft::$app->elements->getElementTypeById($sourceId);
                $parent = null;
                $editUrl = null;

                switch ($elementType) {
                    case 'craft\elements\MatrixBlock':
                        $matrix = Craft::$app->matrix->getBlockById($sourceId);
                        $parent = $this->getTopOwner($matrix);
                        break;
                    case 'benf\neo\elements\Block':
                        $neo = \benf\neo\Plugin::$plugin->blocks->getBlockById($sourceId);
                        $parent = $this->getTopOwner($neo);
                        break;
                }

                // if the element is referenced but not used in any entry, continue
                if (($elementType == 'craft\elements\MatrixBlock' || $elementType == 'benf\neo\elements\Block') && !$parent) {
                    continue;
                }

                $edit = $element;

                if ($parent !== null) {
                    $edit = $parent;
                }

                $elementType = Craft::$app->elements->getElementTypeById($edit->id);

                if ($elementType) {
                    switch ($elementType) {
                        case Entry::class:
                            $editUrl = $edit->getCpEditUrl();
                            break;
                    }

                    $arrReturn[] = [
                        'sourceElement' => $sourceElement,
                        'field'         => $field,
                        'element'       => $element,
                        'parent'        => $parent,
                        'editUrl'       => $editUrl,
                    ];
                }
            }
        }

        return $arrReturn;
    }

    /**
     * Get the top owner of the given element
     *
     * @param $element
     * @return mixed
     */
    private function getTopOwner($element)
    {
        if (!method_exists($element, 'getOwner')) {
            return null;
        }

        $parent = $element->getOwner();

        while ($parent) {
            if (method_exists($parent, 'getOwner')) {
                $parent = $parent->getOwner();
            } else {
                // getOwner() is not possible anymore
                break;
            }
        }

        return $parent;
    }
}
