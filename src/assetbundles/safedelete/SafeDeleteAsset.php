<?php
/**
 * Safedelete plugin for Craft CMS 3.x
 *
 * Delete elements without breaking relations
 *
 * @link      goldinteractive.ch
 * @copyright Copyright (c) 2020 Goldinteractive
 */

namespace goldinteractive\safedelete\assetbundles\safedelete;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 */
class SafeDeleteAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@goldinteractive/safedelete/assetbundles/safedelete/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Safedelete.js',
        ];

        $this->css = [
            'css/Safedelete.css',
        ];

        parent::init();
    }
}
