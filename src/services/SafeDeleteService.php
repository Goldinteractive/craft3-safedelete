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

    protected function getRelationsForElement($id)
    {
        //todo
        return [];
    }
}
