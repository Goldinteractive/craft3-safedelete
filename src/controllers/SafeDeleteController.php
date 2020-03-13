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

}
