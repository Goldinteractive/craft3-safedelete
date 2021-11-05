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
        $plugin = SafeDelete::$plugin;
        $request = Craft::$app->getRequest();
        $ids = $request->getParam('ids', []);
        $type = $request->getParam('type');

        $this->validateParams($ids, $type);

        $relations = $plugin->safeDelete->getUsagesFor($ids, $type);

        if (count($relations) === 0) { // safe to delete

            $plugin->safeDelete->deleteElementsByIds($ids);

            $response = [
                'success' => true,
                'message' => $this->setMessage($type),
            ];
        } else {
            $response = [
                'html'    => $this->getDeleteOverlay($relations),
                'success' => true,
            ];            
        }

        return $this->asJson($response);
    }

    public function actionForceDelete()
    {
        $plugin = SafeDelete::$plugin;
        $request = Craft::$app->getRequest();
        $ids = $request->getParam('ids', []);
        $type = $request->getParam('type');

        $this->validateParams($ids, $type);

        if ($plugin->settings->allowForceDelete) {

            $plugin->safeDelete->deleteElementsByIds($ids);
            $response = [
                'success' => true,
                'message' => $this->setMessage($type),
            ];
        } else {
            $response = [
                'success' => false,
            ];            
        }

        return $this->asJson($response);
    }

    public function actionDeleteUnreferenced()
    {
        $plugin = SafeDelete::$plugin;
        $request = Craft::$app->getRequest();
        $ids = $request->getParam('ids', []);
        $type = $request->getParam('type');

        $this->validateParams($ids, $type);

        $ids = $plugin->safeDelete->filterReferencedIds($ids, $type);

        $plugin->safeDelete->deleteElementsByIds($ids);

        return $this->asJson([
            'success' => true,
            'message' => $this->setMessage($type)
        ]);
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

    private function getDeleteOverlay(array $relations)
    {
        return Craft::$app->view->renderTemplate(
            'safeDelete/deleteOverlay',
            ['relations' => $relations, 'allowForceDelete' => (bool)SafeDelete::$plugin->settings->allowForceDelete]
        );
    }

    /**
     * Set response message
     * 
     * @param string $type
     * @return string 
     */
    private function setMessage(string $type) : string
    {
        switch ($type) {
            case 'asset':
                $str = 'Assets';
                break;
            case 'element':
                $str = 'Elements';
                break;
        }

        return Craft::t('safedelete', $str . ' deleted.');
    }
}
