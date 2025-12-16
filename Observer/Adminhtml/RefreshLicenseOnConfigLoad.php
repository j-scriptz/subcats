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

namespace Jscriptz\Subcats\Observer\Adminhtml;

use Jscriptz\Subcats\Model\License\ApiClient;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer RefreshLicenseOnConfigLoad
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class RefreshLicenseOnConfigLoad implements ObserverInterface
{
    /**
     * Request interface
     *
     * @var RequestInterface
     */
    private RequestInterface $_request;

    /**
     * API client instance
     *
     * @var ApiClient
     */
    private ApiClient $_apiClient;

    /**
     * Logger interface
     *
     * @var LoggerInterface
     */
    private LoggerInterface $_logger;

    /**
     * Store manager interface
     *
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $_storeManager;

    /**
     * Constructor.
     *
     * @param RequestInterface      $request      Request interface
     * @param ApiClient             $apiClient    API client instance
     * @param LoggerInterface       $logger       Logger interface
     * @param StoreManagerInterface $storeManager Store manager interface
     */
    public function __construct(
        RequestInterface $request,
        ApiClient $apiClient,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->_request = $request;
        $this->_apiClient = $apiClient;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
    }

    /**
     * Execute.
     *
     * @param Observer $observer Event observer
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer): void
    {
        // Only for the Jscriptz section (<section id="jscriptz">)
        $section = (string)$this->_request->getParam('section');
        if ($section !== 'jscriptz') {
            return;
        }

        // Only when viewing the page, not saving
        if (strtoupper($this->_request->getMethod()) !== 'GET') {
            return;
        }

        [$scopeType, $scopeId] = $this->_resolveScope();

        try {
            // Update + News (Version Status / Jscriptz News & Updates)
            $this->_apiClient->syncUpdateInfo($scopeType, $scopeId);

            // Verify (License Status / Last Verify Response)
            $this->_apiClient->syncVerifyInfo($scopeType, $scopeId);
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Jscriptz_Subcats: license refresh on config load failed: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Determine which scope the System Config page is currently using.
     *
     * @return array{0:string,1:int} [$scopeType, $scopeId]
     */
    private function _resolveScope(): array
    {
        $storeCode   = (string)$this->_request->getParam('store');
        $websiteCode = (string)$this->_request->getParam('website');

        // Store view scope (e.g. ?store=default)
        if ($storeCode !== '') {
            try {
                $store = $this->_storeManager->getStore($storeCode);
                return [ScopeInterface::SCOPE_STORE, (int)$store->getId()];
            } catch (\Throwable $e) {
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                unset($e); // Fall through to website/default
            }
        }

        // Website scope (e.g. ?website=base)
        if ($websiteCode !== '') {
            try {
                $website = $this->_storeManager->getWebsite($websiteCode);
                return [ScopeInterface::SCOPE_WEBSITE, (int)$website->getId()];
            } catch (\Throwable $e) {
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                unset($e); // Fall through to default
            }
        }

        // Default config scope
        return [ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0];
    }
}
