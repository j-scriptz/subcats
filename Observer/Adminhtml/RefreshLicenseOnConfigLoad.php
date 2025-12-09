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
 */
class RefreshLicenseOnConfigLoad implements ObserverInterface
{
    private RequestInterface $request;
    private ApiClient $apiClient;
    private LoggerInterface $logger;
    private StoreManagerInterface $storeManager;

    /**
     * Constructor.
     *
     * @param RequestInterface $request
     * @param ApiClient $apiClient
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RequestInterface $request,
        ApiClient $apiClient,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->request      = $request;
        $this->apiClient    = $apiClient;
        $this->logger       = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        // Only for the Jscriptz section (<section id="jscriptz">)
        $section = (string)$this->request->getParam('section');
        if ($section !== 'jscriptz') {
            return;
        }

        // Only when viewing the page, not saving
        if (strtoupper($this->request->getMethod()) !== 'GET') {
            return;
        }

        [$scopeType, $scopeId] = $this->resolveScope();

        try {
            // Update + News (Version Status / Jscriptz News & Updates)
            $this->apiClient->syncUpdateInfo($scopeType, $scopeId);

            // Verify (License Status / Last Verify Response)
            $this->apiClient->syncVerifyInfo($scopeType, $scopeId);
        } catch (\Throwable $e) {
            $this->logger->error(
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
    private function resolveScope(): array
    {
        $storeCode   = (string)$this->request->getParam('store');
        $websiteCode = (string)$this->request->getParam('website');

        // Store view scope (e.g. ?store=default)
        if ($storeCode !== '') {
            try {
                $store = $this->storeManager->getStore($storeCode);
                return [ScopeInterface::SCOPE_STORE, (int)$store->getId()];
            } catch (\Throwable $e) {
                // fall through to website/default
            }
        }

        // Website scope (e.g. ?website=base)
        if ($websiteCode !== '') {
            try {
                $website = $this->storeManager->getWebsite($websiteCode);
                return [ScopeInterface::SCOPE_WEBSITE, (int)$website->getId()];
            } catch (\Throwable $e) {
                // fall through to default
            }
        }

        // Default config scope
        return [ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0];
    }
}
