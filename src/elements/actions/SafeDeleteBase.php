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
use craft\base\ElementAction;
use goldinteractive\safedelete\SafeDelete;

/**
 * @author    Goldinteractive
 * @package   Safedelete
 * @since     1.0.0
 */
abstract class SafeDeleteBase extends ElementAction
{
    abstract public function getDeletionType();

    abstract public function getOriginalAction();

    abstract public function getDeletionHandle();

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $settings = SafeDelete::$plugin->getSettings();
        $confirmMessage = $this->getConfirmationMessage();
        $deletionType = $this->getDeletionType();
        $originalAction = $this->getOriginalAction();
        $deletionHandle = $this->getDeletionHandle();
        $hideDefaultDeleteAction = $settings->hideDefaultDeleteAction ? 'true' : 'false';

        // backslashes get escaped in html with 1 slash and a backslash must be
        // escaped by 2 backslashes for the selector, so we actually need 4 backslashes everywhere
        $originalAction = str_replace('\\','\\\\\\\\',$originalAction);

        //todo remove of the original action is not working yet, we call the .remove() too soon

        $js = <<<EOT
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$deletionHandle},
        batch: true,
        validateSelection: function(\$selectedItems)
        {
            return true;
        },
        activate: function(\$selectedItems)
        {
          if(confirm('$confirmMessage')) {
                 var ids = [];
      
                \$selectedItems.each(function(index, el) {
                    var \$el = \$(el);
                    ids.push(\$el.data('id'));
                });
                                
                var doAction = function(ids) {
                      Craft.postActionRequest('safedelete/safe-delete/try-delete', {'ids': ids, type: '$deletionType'}, function(res) {
                        if(res.success && res.html) {
                           var \$html = $('<div class="modal">'+res.html+'</div>'),
                           modal = new Garnish.Modal(\$html);
                           
                           
                           \$html.find('.cancel').on('click', function() {
                            modal.hide();
                           });
                           
                           \$html.find('.submit[name=forceDelete]').on('click', function() {
                               Craft.postActionRequest('safedelete/safe-delete/force-delete', {'ids': ids, type: '$deletionType'}, function(res) {
                                    Craft.elementIndex.updateElements();
                                    modal.hide();
                               });
                           });
                           
                           \$html.find('.submit[name=deleteUnreferencedElements]').on('click', function() {
                               Craft.postActionRequest('safedelete/safe-delete/delete-unreferenced', {'ids': ids, type: '$deletionType'}, function(res) {
                                    Craft.elementIndex.updateElements();
                                    modal.hide();
                               });
                           });
                           
                           \$html.find('.reload').on('click', function() {
                            modal.hide();
                            doAction(ids);
                           });
                           
                        } else if(res.success) {
                            Craft.elementIndex.updateElements();
                        }
                    });
                }
                
                doAction(ids);
		    }
		}
	});
	
	//remove default delete
	if($hideDefaultDeleteAction) {
		 $('[data-action=$originalAction').remove();
	}
})();
EOT;

        Craft::$app->getView()->registerJs($js);
    }
}
