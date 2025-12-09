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


namespace Jscriptz\Subcats\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Model LicenseValidator
 */
class LicenseValidator
{
    const XML_PATH_LICENSE_KEY    = 'jscriptz_subcats/license/license_key';
    const XML_PATH_LICENSE_STATUS = 'jscriptz_subcats/license/license_status';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if license is valid for the given store.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isValid($storeId = null)
    {
        $key = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_LICENSE_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($key === '') {
            return false;
        }

        $status = (string)$this->scopeConfig->getValue(
            self::XML_PATH_LICENSE_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // Consider the license valid if the status text contains "License verified"
        return stripos($status, 'License verified') !== false;
    }


    /**
     * Get raw license status string.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getStatus($storeId = null)
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_LICENSE_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
