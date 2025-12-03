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

class LicenseKey extends Field
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    public function render(AbstractElement $element)
    {
        // No "Use Website / Use Default" for the license key
        $element->setCanUseWebsiteValue(false);
        $element->setCanUseDefaultValue(false);
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        // Read the human-readable status message we store in config
        $statusMessage = (string)$this->scopeConfig->getValue(
            'jscriptz_subcats/license/license_status'
        );

        // Lock the field only when we know the license has been successfully verified
        $hasVerified = (stripos($statusMessage, 'License verified') !== false);

        if ($hasVerified) {
            // Make the input uneditable
            $element->setReadonly(true);
            $element->setData('disabled', 'disabled');

            // Optional: add a small note under the field
            $note = $element->getNote();
            $extraNote = (string)__(
                'License key locked after successful verification. Contact support if you need to change it.'
            );
            $element->setNote($note ? $note . ' ' . $extraNote : $extraNote);
        }

        return parent::_getElementHtml($element);
    }
}
