<?php
/**
 * Safedelete plugin for Craft CMS 3.x
 *
 * Delete elements without breaking relations
 *
 * @link      https://goldinteractive.ch
 * @copyright Copyright (c) 2020 Goldinteractive
 */

namespace goldinteractive\safedelete\elements\actions;

use Craft;
use craft\elements\actions\DeleteAssets;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;

/**
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 */
class SafeDeleteAssets extends SafeDeleteBase
{
    /**
     * @return string
     */
    public function getDeletionType()
    {
        return 'asset';
    }

    /**
     * @return string
     */
    public function getOriginalAction()
    {
        return Json::encode(DeleteAssets::class);
    }

    /**
     * @return string
     */
    public function getDeletionHandle()
    {
        return Json::encode(static::class);
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('safedelete', 'Safe Delete');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        return Craft::t('app', 'Are you sure you want to delete the selected assets?');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        return false;
    }
}
