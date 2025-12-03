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

        // Default styling (valid / neutral)
        $color = '#3f9c35'; // green-ish
        $fontWeight = 'bold';

        $lower = strtolower($value);

        // Mark inactive / invalid / not found as red
        if (
            str_contains($lower, 'inactive')
            || str_contains($lower, 'not valid')
            || str_contains($lower, 'not found')
            || str_contains($lower, 'verification error')
            || str_contains($lower, 'expired')
        ) {
            $color = '#b30000'; // red
        } elseif (str_contains($lower, 'trial')) {
            // Optional: trial = amber
            $color = '#e0a800';
        }

        return sprintf(
            '<span style="color:%s;font-weight:%s;">%s</span>',
            $color,
            $fontWeight,
            $this->escapeHtml($value)
        );
    }
}
