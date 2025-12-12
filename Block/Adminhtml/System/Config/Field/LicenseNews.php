<?php
declare(strict_types=1);

/**
 * Jscriptz LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://mage.jscriptz.com/LICENSE
 *
 ********************************************************************
 *
 * @category   Jscriptz
 * @package    Jscriptz_Subcats
 * @author     Jason Lotzer (jasonlotzer@gmail.com)
 * @copyright  Copyright (c) 2019 Jscriptz LLC. (https://mage.jscriptz.com)
 * @license    https://mage.jscriptz.com/LICENSE.txt
 */


namespace Jscriptz\Subcats\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Block LicenseNews
 */
class LicenseNews extends Field
{
    /**
     * Render field.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        // No "Use Default / Use Website" toggles for this read-only field
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        return parent::render($element);
    }

    /**
     * Get element HTML.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        // Read straight from the shared License config path.
        // This will pull the effective value (default/website/store) just like Magento does.
        $value = (string)$this->_scopeConfig->getValue('jscriptz_subcats/license/news_message');

        if (trim($value) === '') {
            $value = (string)__('No information available.');
        }

        // Don't escape here so your <a> link renders as HTML
        return '<div>' . $value . '</div>';
    }
}
