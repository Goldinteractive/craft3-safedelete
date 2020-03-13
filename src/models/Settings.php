<?php
/**
 * Safedelete plugin for Craft CMS 3.x
 *
 * Delete elements without breaking relations
 *
 * @link      https://goldinteractive.ch
 * @copyright Copyright (c) 2020 Goldinteractive
 */

namespace goldinteractive\safedelete\models;

use goldinteractive\safedelete\SafeDelete;

use Craft;
use craft\base\Model;

/**
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $hideDefaultDeleteAction = true;

    /**
     * @var bool
     */
    public $allowForceDelete = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hideDefaultDeleteAction','allowForceDelete'], 'boolean'],
        ];
    }
}
