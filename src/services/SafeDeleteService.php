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
use yii\base\InvalidConfigException;
use craft\base\ElementInterface;
use craft\helpers\Json;

/**
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 */
class SafeDeleteService extends Component
{
    /**
     * Delete elements by ids
     * 
     * @param array $ids
     * @throws \Throwable
     * @return void
     */
    public function deleteElementsByIds(array $ids) : void
    {
        foreach ($ids as $id) {
            Craft::$app->elements->deleteElementById($id);
        }        
    }

    /**
     * @param array  $ids
     * @param string $type
     * @return array
     */
    public function getRelations(array $ids, string $type) : array
    {
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

        return $relations ?? [];
    }

    /**
     * Returns an array only with ids which are
     * not referenced and safe to delete.
     *
     * @param array  $ids
     * @param string $type
     * @return array
     */
    public function filterReferencedIds(array $ids, string $type) : array
    {
        $arrIds = [];
        $arrRet = [];

        $relations = $this->getRelations($ids, $type);

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

    /**
     * @param string $id
     * @param int    $limit the max amount of relations the function will return (invalid relations will be ignored by the count)
     * @return array
     */
    protected function getRelationsForElement(string $id, int $limit = 5) // todo implement hasMore return
    {
        $count = 0;
        $arrReturn = [];

        $sourceElement = Craft::$app->elements->getElementById($id);
        $sites = Craft::$app->sites->getAllSites();

        $results = $this->getRelationsDataByTargetId($id);

        $search = $this->searchForElementRelations($limit, $count, $sourceElement, $sites, $results);
        $arrReturn = array_merge($arrReturn, $search['results']);
        $count = $search['count'];

        // continue with custom searches for relations
        if ($count < $limit) {
            // support for fruitstudios/linkit plugin
            if (Craft::$app->plugins->isPluginEnabled('linkit')) {
                $search = $this->searchForLinkItPluginRelations($limit, $count, $sourceElement);
                $arrReturn = array_merge($arrReturn, $search['results']);
                $count = $search['count'];
            }
        }

        return $arrReturn;
    }

    /**
     * Get data by target id
     * @param int $id
     * @return array
     */
    private function getRelationsDataByTargetId(int $id) : array
    {
        return (new Query())->select('fieldId, sourceId')->from('{{%relations}}')->where(
            'targetId = :targetId',
            ['targetId' => $id]
        )->all();
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

        try {
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

        } catch (InvalidConfigException $e) {
            // catch invalid owner ID exception
            // this will happen if an entry is deleted but the content for this entry is not
            return null;
        }
    }

    private function searchForElementRelations(
        int $limit,
        int $count,
        $sourceElement,
        array $sites,
        array $results
    ) {
        $arrReturn = [];

        foreach ($results as $relation) {
            if ($count >= $limit) {
                break;
            }

            $sourceId = $relation['sourceId'];

            $field = Craft::$app->fields->getFieldById($relation['fieldId']);

            foreach ($sites as $site) {

                $element = Craft::$app->elements->getElementById($sourceId, null, $site->id);

                if ($element !== null) {
                    $elementType = Craft::$app->elements->getElementTypeById($sourceId);

                    $parent = $this->getBlockParentElement($elementType, $sourceId, $site->id);

                    // if the element is referenced but not used in any entry, continue
                    if (($elementType == 'craft\elements\MatrixBlock' || $elementType == 'benf\neo\elements\Block') && !$parent) {
                        continue;
                    }

                    $edit = $parent !== null ? $parent : $element;

                    $editUrl = $this->resolveElementEditUrl($edit);

                    if ($editUrl) {

                        $arrReturn[] = [
                            'sourceElement' => $sourceElement,
                            'field'         => $field,
                            'element'       => $element,
                            'parent'        => $parent,
                            'editUrl'       => $editUrl,
                            'site'          => $site->name,
                        ];

                        $count++;
                    }
                }
            }
        }

        return [
            'count'   => $count,
            'results' => $arrReturn,
        ];
    }

    /**
     * Get block parent entry
     * @param $type
     * @param int $sourceId
     * @param $siteId
     * @return ElementInterface|null 
     */
    private function getBlockParentElement($type, $sourceId, $siteId) : ?ElementInterface
    {
        switch ($type) {
            case 'craft\elements\MatrixBlock':
                $matrix = Craft::$app->matrix->getBlockById($sourceId, $siteId);
                $parent = $this->getTopOwner($matrix);
                break;
            case 'benf\neo\elements\Block':
                $neo = \benf\neo\Plugin::$plugin->blocks->getBlockById($sourceId, $siteId);
                $parent = $this->getTopOwner($neo);
                break;
        }

        return $parent ?? null;
    }

    /**
     * Resolve element cp edit url
     * 
     * @param ElementInterface $edit
     * @return string|null
     */
    private function resolveElementEditUrl(ElementInterface $edit) : ?string
    {
        $elementType = Craft::$app->elements->getElementTypeById($edit->id);

        switch ($elementType) {
            case Entry::class:
                if ($edit->getIsRevision()) {
                    // ignore this result
                    return null;
                }

                return $edit->getCpEditUrl();
        
        }
    }

    private function searchForLinkItPluginRelations(
        int $limit,
        int $count,
        $sourceElement
    ) {
        $arrReturn = [];
        $id = $sourceElement->id;

        // the value goes like this:  {"type":"fruitstudios\\linkit\\models\\Asset","value":"20","customText":"...","target":"1"}
        $filterValue = '\'%"value":"' . $id . '"%\'';

        $customResults = SafeDelete::$plugin->field->getAllFieldValuesFromFieldType('fruitstudios\linkit\fields\LinkitField', $filterValue, true);

        foreach ($customResults as $fieldResults) {
            $fieldId = $fieldResults['field']['id'];

            foreach ($fieldResults['content'] as $content) {
                if ($count >= $limit) {
                    break 2;
                }

                $fieldValue = $content['field'];

                $decoded = json_decode($fieldValue, true);
                $possibleTypes = [
                    'fruitstudios\\linkit\\models\\Asset',
                    'fruitstudios\\linkit\\models\\Entry',
                ];

                if (
                    !is_array($decoded) ||
                    !isset($decoded['type']) ||
                    !isset($decoded['value']) ||
                    !in_array($decoded['type'], $possibleTypes) ||
                    $decoded['value'] !== $id
                ) {
                    continue;
                }

                // we have a match!

                $site = Craft::$app->sites->getSiteById($content['siteId']);

                if ($site !== null) {
                    // do same as above but with modified parameters

                    $results = [
                        [
                            'fieldId'  => $fieldId,
                            'sourceId' => $content['elementId'],
                        ],
                    ];

                    $sites = [$site];

                    $search = $this->searchForElementRelations($limit, $count, $sourceElement, $sites, $results);
                    $arrReturn = array_merge($arrReturn, $search['results']);
                    $count = $search['count'];
                }
            }
        }

        return [
            'count'   => $count,
            'results' => $arrReturn,
        ];
    }
}
