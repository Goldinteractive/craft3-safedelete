<?php
/**
 * Safedelete plugin for Craft CMS 3.x
 *
 * Delete elements without breaking relations
 *
 * @link      https://goldinteractive.ch
 * @copyright Copyright (c) 2020 Goldinteractive
 */

namespace goldinteractive\safedelete\controllers;

use goldinteractive\safedelete\SafeDelete;

use Craft;
use craft\web\Controller;

/**
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 */
class SafeDeleteController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = false;

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionTryDelete()
    {
        $request = Craft::$app->getRequest();
        $ids = $request->getParam('ids', []);
        $type = $request->getParam('type');

        $this->validateParams($ids, $type);

        $settings = SafeDelete::$plugin->getSettings();

        $relations = SafeDelete::$plugin->safeDelete->getUsagesFor($ids, $type);

        if ($relations === null || count($relations) === 0) { // safe to delete
            return SafeDelete::$plugin->safeDelete->doAction($ids, $type);
        } else {
            $html = Craft::$app->view->renderTemplate(
                'safeDelete/deleteOverlay',
                ['relations' => $relations, 'allowForceDelete' => (bool)$settings->allowForceDelete]
            );

            return $this->asJson(
                [
                    'html'    => $html,
                    'success' => true,
                ]
            );
        }
    }

    public function actionForceDelete()
    {
        $request = Craft::$app->getRequest();
        $ids = $request->getParam('ids', []);
        $type = $request->getParam('type');

        $this->validateParams($ids, $type);

        $settings = SafeDelete::$plugin->getSettings();

        if ($settings->allowForceDelete) {

            return SafeDelete::$plugin->safeDelete->doAction($ids, $type);
        }

        return $this->asJson(
            [
                'success' => false,
            ]
        );
    }

    public function actionDeleteUnreferenced()
    {
        $request = Craft::$app->getRequest();
        $ids = $request->getParam('ids', []);
        $type = $request->getParam('type');

        $this->validateParams($ids, $type);

        $ids = SafeDelete::$plugin->safeDelete->filterReferencedIds($ids, $type);

        return SafeDelete::$plugin->safeDelete->doAction($ids, $type);
    }

    protected function doAction($ids, $type)
    {
        $message = '';

        switch ($type) {
            case 'asset':
                $message = Craft::t('safedelete', 'Assets deleted.');
                break;
            case 'element':
                $message = Craft::t('safedelete', 'Elements deleted.');
                break;
        }

        foreach ($ids as $id) {
            Craft::$app->elements->deleteElementById($id);
        }

        return $this->asJson(
            [
                'success' => true,
                'message' => $message,
            ]
        );
    }

    /**
     * Very very basic validation
     *
     * @param $ids
     * @param $type
     * @return void|\yii\web\Response
     */
    protected function validateParams($ids, $type)
    {
        if (is_array($ids) && is_string($type)) {
            return;
        }

        return $this->asJson(
            [
                'success' => false,
                'message' => Craft::t('safedelete', 'Bad parameters.'),
            ]
        );
    }
}
