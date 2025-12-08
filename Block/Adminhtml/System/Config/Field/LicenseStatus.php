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

class LicenseStatus extends Field
{
    public function render(AbstractElement $element)
    {
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $value = (string)$element->getValue();
        $lower = strtolower($value);

        // Default styling
        $color = '#666666'; // gray for unknown states
        $fontWeight = 'bold';
        $icon = '';

        // Priority 1: Active/Valid licenses (GREEN)
        if (str_contains($lower, 'active') 
            || str_contains($lower, 'valid')
            || str_contains($lower, 'verified')
        ) {
            $color = '#3f9c35'; // green
            $icon = '✓ ';
        }
        // Priority 2: Invalid/Expired/Error states (RED)
        elseif (
            str_contains($lower, 'inactive')
            || str_contains($lower, 'not valid')
            || str_contains($lower, 'not found')
            || str_contains($lower, 'verification error')
            || str_contains($lower, 'expired')
            || str_contains($lower, 'suspended')
        ) {
            $color = '#b30000'; // red
            $icon = '✗ ';
        }
        // Priority 3: Trial states (AMBER)
        elseif (str_contains($lower, 'trial')) {
            $color = '#e0a800'; // amber
            $icon = '⏱ ';
        }

        return sprintf(
            '<span style="color:%s;font-weight:%s;">%s%s</span>',
            $color,
            $fontWeight,
            $icon,
            $this->escapeHtml($value)
        );
    }
}
