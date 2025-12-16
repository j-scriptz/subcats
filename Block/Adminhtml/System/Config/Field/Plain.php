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
 * PHP version 7
 *
 * @category  Jscriptz
 * @package   Jscriptz_Subcats
 * @author    Jason Lotzer <jasonlotzer@gmail.com>
 * @copyright 2019 Jscriptz LLC
 * @license   https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link      https://mage.jscriptz.com
 */


namespace Jscriptz\Subcats\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Block Plain
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class Plain extends Field
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
        // Hide "Use Default / Use Website" toggles for this read-only field
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
        // 1) Whatever Magento already resolved
        $value = (string)$element->getValue();

        // 2) If it's empty, manually resolve via config_path
        if ($value === '') {
            $originalData = $element->getOriginalData(); // from system.xml
            if (!empty($originalData['config_path'])) {
                $configPath = $originalData['config_path'];
                $configValue = (string)$this->_scopeConfig->getValue($configPath);

                if ($configValue !== '') {
                    $value = $configValue;
                }
            }
        }

        // 3) Final fallback
        if (trim($value) === '') {
            $value = (string)__('No information available.');
        }

        // Allow HTML in the config value (for your <a> link)
        return '<div>' . $value . '</div>';
    }
}
