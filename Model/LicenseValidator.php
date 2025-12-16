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


namespace Jscriptz\Subcats\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Model LicenseValidator
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class LicenseValidator
{
    public const XML_PATH_LICENSE_KEY = 'jscriptz_subcats/license/license_key';
    public const XML_PATH_LICENSE_STATUS = 'jscriptz_subcats/license/license_status';

    /**
     * Scope config instance.
     *
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig Scope config instance
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Check if license is valid for the given store.
     *
     * @param int|null $storeId Store ID
     *
     * @return bool
     */
    public function isValid($storeId = null)
    {
        $key = trim(
            (string)$this->_scopeConfig->getValue(
                self::XML_PATH_LICENSE_KEY,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );

        if ($key === '') {
            return false;
        }

        $status = (string)$this->_scopeConfig->getValue(
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
     * @param int|null $storeId Store ID
     *
     * @return string
     */
    public function getStatus($storeId = null)
    {
        return (string)$this->_scopeConfig->getValue(
            self::XML_PATH_LICENSE_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
