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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;

/**
 * Block LicenseKey
 */
class LicenseKey extends Field
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Render
     *
     * @param AbstractElement $element
     */
    public function render(AbstractElement $element)
    {
        // No "Use Website / Use Default" for the license key
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        // Read both status fields to determine if license is valid
        $licenseStatus = strtolower((string)$this->scopeConfig->getValue(
            'jscriptz_subcats/license/license_status'
        ));
        $verifyMessage = strtolower((string)$this->scopeConfig->getValue(
            'jscriptz_subcats/license/verify_message'
        ));

        // Check if license is active/valid in either field
        // This handles both the /update response ("License is active.")
        // and /verify response ("License is valid.")
        $isLicenseValid = false;

        // Check license_status for active/valid indicators
        if (str_contains($licenseStatus, 'active')
            || str_contains($licenseStatus, 'valid')
            || str_contains($licenseStatus, 'verified')
        ) {
            $isLicenseValid = true;
        }

        // Also check verify_message as fallback
        if (str_contains($verifyMessage, 'valid')
            || str_contains($verifyMessage, 'active')
            || str_contains($verifyMessage, 'verified')
        ) {
            $isLicenseValid = true;
        }

        // Don't lock if it's a trial or if there's no license key
        $licenseKey = (string)$element->getValue();
        if (empty(trim($licenseKey))
            || str_contains($licenseStatus, 'trial')
            || str_contains($licenseStatus, 'expired')
            || str_contains($verifyMessage, 'not found')
        ) {
            $isLicenseValid = false;
        }

        if ($isLicenseValid) {
            // Make the input readonly and visually disabled
            $element->setReadonly(true);
            $element->setData('disabled', 'disabled');

            // Add explanatory note
            $note = $element->getNote();
            $extraNote = (string)__(
                '🔒 License key locked after successful verification. <a href="mailto:support@jscriptz.com">Contact support</a> to change it.'
            );
            $element->setNote($note ? $note . ' ' . $extraNote : $extraNote);
        }

        return parent::_getElementHtml($element);
    }
}
