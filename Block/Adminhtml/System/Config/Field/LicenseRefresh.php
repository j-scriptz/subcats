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
 * @copyright  Copyright (c) 2019-2025 Jscriptz LLC. (https://mage.jscriptz.com)
 * @license    https://mage.jscriptz.com/LICENSE.txt
 */

namespace Jscriptz\Subcats\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Block LicenseRefresh
 */
class LicenseRefresh extends Field
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
        $html = '<div id="jscriptz-license-refresh" class="jscriptz-license-refresh" '
            . 'style="display:flex;align-items:center;gap:8px;">'
            . '<span class="jscriptz-license-spinner"></span>'
            . '<span class="jscriptz-license-text">'
            . __('Refreshing license info...')
            . '</span>'
            . '</div>';

        // Simple inline spinner CSS
        $html .= '<style>
            @keyframes jscriptz-license-spin {
                0%   { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .jscriptz-license-spinner {
                width: 14px;
                height: 14px;
                border-radius: 50%;
                border: 2px solid #dcdcdc;
                border-top-color: #1979c3;
                display: inline-block;
                box-sizing: border-box;
                animation: jscriptz-license-spin 0.8s linear infinite;
            }
        </style>';

        // After ~1.2s, hide spinner and change text
        $html .= '<script type="text/javascript">
            require(["jquery"], function($) {
                $(function() {
                    setTimeout(function() {
                        var $wrap = $("#jscriptz-license-refresh");
                        if (!$wrap.length) {
                            return;
                        }
                        $wrap.find(".jscriptz-license-spinner").hide();
                        $wrap.find(".jscriptz-license-text").text("'
            . __('License info was refreshed when this page loaded.')
            . '");
                    }, 1200);
                });
            });
        </script>';

        return $html;
    }
}
