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
 * Block Header
 */
class Header extends Field
{
    /**
     * Render
     *
     * @param AbstractElement $element
     */
    public function render(AbstractElement $element)
    {
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $logoUrl = $this->getViewFileUrl('Jscriptz_Subcats::images/jscriptz-logo.svg');

        $html  = '<div class="jscriptz-license-header" style="display:flex;align-items:center;gap:12px;margin:10px 0;">';
        $html .= sprintf(
            '<img src="%s" alt="%s" style="height:32px;max-width:180px;" />',
            $logoUrl,
            $this->escapeHtmlAttr(__('Jscriptz Subcats'))
        );
        $html .= '<div style="font-size:13px;line-height:1.4;">';
        $html .= 'Get a License <a href="https://mage.jscriptz.com/jscriptz-subcats.html" target="_blank">Here</a>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
