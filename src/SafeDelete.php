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
use craft\elements\actions\Delete;
use craft\elements\actions\DeleteAssets;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\events\RegisterElementActionsEvent;
use goldinteractive\safedelete\assetbundles\safedelete\SafeDeleteAsset;
use goldinteractive\safedelete\elements\actions\SafeDeleteAssets;
use goldinteractive\safedelete\elements\actions\SafeDeleteElements;
use goldinteractive\safedelete\services\FieldService;
use goldinteractive\safedelete\services\SafeDeleteService;
use goldinteractive\safedelete\models\Settings;

use Craft;
use craft\base\Plugin;

use yii\base\Event;

/**
 * Class SafeDelete
 *
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 *
 * @property  SafeDeleteService $safeDelete
 * @property  FieldService      $field
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
                'field'      => FieldService::class,
            ]
        );

        Event::on(Asset::class, Asset::EVENT_REGISTER_ACTIONS, function (RegisterElementActionsEvent $event) {
            if (preg_match('/^folder:([a-z0-9\-]+)/', $event->source, $matches)) {
                $folderId = $matches[1];

                $folder = Craft::$app->getAssets()->getFolderByUid($folderId);
                /** @var Volume $volume */
                $volume = $folder->getVolume();

                if (Craft::$app->user->checkPermission('deleteFilesAndFoldersInVolume:' . $volume->uid)) {
                    $settings = SafeDelete::$plugin->getSettings();
                    if($settings->hideDefaultDeleteAction) {
                        $this->disableActions($event->actions, [DeleteAssets::class]);
                    }

                    $event->actions[] = new SafeDeleteAssets();
                }
            }
        });

        Event::on(Entry::class, Entry::EVENT_REGISTER_ACTIONS, function (RegisterElementActionsEvent $event) {
            $source = $event->source;
            $section = null;

            if ($source !== '*' && $source !== 'singles') {
                if (preg_match('/^section:(\d+)$/', $source, $matches)) {
                    $section = Craft::$app->getSections()->getSectionById($matches[1]);

                } else if (preg_match('/^section:(.+)$/', $source, $matches)) {
                    $section = Craft::$app->getSections()->getSectionByUid($matches[1]);
                }

                $userSession = Craft::$app->getUser();

                if ($section !== null &&
                    $userSession->checkPermission('deleteEntries:' . $section->uid) &&
                    $userSession->checkPermission('deletePeerEntries:' . $section->uid)
                ) {
                    $settings = SafeDelete::$plugin->getSettings();
                    if($settings->hideDefaultDeleteAction) {
                        $this->disableActions($event->actions, [Delete::class]);
                    }

                    $event->actions[] = new SafeDeleteElements();

                }
            }
        });

        Event::on(Category::class, Category::EVENT_REGISTER_ACTIONS, function (RegisterElementActionsEvent $event) {
            $source = $event->source;

            // Get the group we need to check permissions on
            if (preg_match('/^group:(\d+)$/', $source, $matches)) {
                $group = Craft::$app->getCategories()->getGroupById($matches[1]);
            } else if (preg_match('/^group:(.+)$/', $source, $matches)) {
                $group = Craft::$app->getCategories()->getGroupByUid($matches[1]);
            }

            if (!empty($group)) {
                $settings = SafeDelete::$plugin->getSettings();
                if($settings->hideDefaultDeleteAction) { 
                    $this->disableActions($event->actions, [Delete::class]);
                }

                $event->actions[] = new SafeDeleteElements();
            }
        });
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
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    protected function settingsHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(SafeDeleteAsset::class);

        return Craft::$app->view->renderTemplate(
            'safedelete/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }

    private function disableActions(array &$actions, array $disabledActions) : void
    {

        foreach ($actions as $key => $action) {
            if (in_array($action, $disabledActions) || (is_array($action) && in_array($action['type'], $disabledActions))) {
                unset($actions[$key]);
            }
        }   
    }
}
