<?php
declare(strict_types=1);

/**
 * Jscriptz LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.
 * It is also available through the web at this URL:
 * https://mage.jscriptz.com/LICENSE.txt
 *
 ********************************************************************
 *
 * PHP Version 8+
 *
 * @category  Jscriptz
 * @package   Jscriptz_Subcats
 * @author    Jason Lotzer <jasonlotzer@gmail.com>
 * @copyright 2019 - 2025 Jscriptz LLC
 * @license   https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link      https://mage.jscriptz.com
 */


namespace Jscriptz\Subcats\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Block LicenseStatus
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class LicenseStatus extends Field
{
    /**
     * Render field.
     *
     * @param AbstractElement $element Form element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        return parent::render($element);
    }

    /**
     * Get element HTML.
     *
     * @param AbstractElement $element Form element
     *
     * @return string
     */
    // @codingStandardsIgnoreLine
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
        } elseif (str_contains($lower, 'inactive')
            || str_contains($lower, 'not valid')
            || str_contains($lower, 'not found')
            || str_contains($lower, 'verification error')
            || str_contains($lower, 'expired')
            || str_contains($lower, 'suspended')
        ) {
            // Priority 2: Invalid/Expired/Error states (RED)
            $color = '#b30000'; // red
            $icon = '✗ ';
        } elseif (str_contains($lower, 'trial')) {
            // Priority 3: Trial states (AMBER)
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
