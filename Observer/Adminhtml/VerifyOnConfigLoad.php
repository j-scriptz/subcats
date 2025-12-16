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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer VerifyOnConfigLoad
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class VerifyOnConfigLoad implements ObserverInterface
{
    /**
     * Request interface
     *
     * @var RequestInterface
     */
    private $_request;

    /**
     * API client instance
     *
     * @var ApiClient
     */
    private $_apiClient;

    /**
     * Logger interface
     *
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param RequestInterface $request   Request interface
     * @param ApiClient        $apiClient API client instance
     * @param LoggerInterface  $logger    Logger interface
     */
    public function __construct(
        RequestInterface $request,
        ApiClient $apiClient,
        LoggerInterface $logger
    ) {
        $this->_request = $request;
        $this->_apiClient = $apiClient;
        $this->_logger = $logger;
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
        // Only when our section is opened
        $section = (string)$this->_request->getParam('section');
        if ($section !== 'jscriptz_subcats') { // adjust if your section id differs
            return;
        }

        // Only on GET (view), not POST (save)
        if (strtoupper($this->_request->getMethod()) !== 'GET') {
            return;
        }

        try {
            // 1) Refresh version + news info
            $this->_apiClient->syncUpdateInfo();

            // 2) Refresh license validity + last verify response
            $this->_apiClient->syncVerifyInfo();
        } catch (\Throwable $e) {
            // Don't break the config page if license server is down
            $this->_logger->error(
                'Jscriptz_Subcats: failed to refresh license info on config load: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
