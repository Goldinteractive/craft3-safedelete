<?php
/**
 * Safedelete plugin for Craft CMS 3.x
 *
 * Delete elements without breaking relations
 *
 * @link      https://goldinteractive.ch
 * @copyright Copyright (c) 2020 Goldinteractive
 */

namespace goldinteractive\safedelete;

use craft\base\Volume;
use craft\elements\Asset;
use craft\events\RegisterElementActionsEvent;
use goldinteractive\safedelete\assetbundles\safedelete\SafedeleteAsset;
use goldinteractive\safedelete\elements\actions\SafeDeleteAssets;
use goldinteractive\safedelete\services\SafeDeleteService;
use goldinteractive\safedelete\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Class SafeDelete
 *
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 *
 * @property  SafeDeleteService $safeDelete
 */
class SafeDelete extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var SafeDelete
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->controllerNamespace = 'goldinteractive\safedelete\controllers';

        $this->setComponents(
            [
                'safeDelete' => SafeDeleteService::class,
            ]
        );

        Event::on(Asset::class, Asset::EVENT_REGISTER_ACTIONS, function (RegisterElementActionsEvent $event) {
            if (preg_match('/^folder:([a-z0-9\-]+)/', $event->source, $matches)) {
                $folderId = $matches[1];

                $folder = Craft::$app->getAssets()->getFolderByUid($folderId);
                /** @var Volume $volume */
                $volume = $folder->getVolume();

                if (Craft::$app->user->checkPermission('deleteFilesAndFoldersInVolume:' . $volume->uid)) {
                    $event->actions[] = new SafeDeleteAssets();
                }
            }
        });

//        Event::on(
//            UrlManager::class,
//            UrlManager::EVENT_REGISTER_CP_URL_RULES,
//            function (RegisterUrlRulesEvent $event) {
//                $event->rules['cpActionTrigger1'] = 'safedelete/safe-delete/do-something';
//            }
//        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(SafedeleteAsset::class);

        return Craft::$app->view->renderTemplate(
            'safedelete/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }
}
