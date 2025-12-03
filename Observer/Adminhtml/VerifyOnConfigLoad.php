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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class VerifyOnConfigLoad implements ObserverInterface
{
    /** @var RequestInterface */
    private $request;

    /** @var ApiClient */
    private $apiClient;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        RequestInterface $request,
        ApiClient $apiClient,
        LoggerInterface $logger
    ) {
        $this->request   = $request;
        $this->apiClient = $apiClient;
        $this->logger    = $logger;
    }

    public function execute(Observer $observer): void
    {
        // Only when our section is opened
        $section = (string)$this->request->getParam('section');
        if ($section !== 'jscriptz_subcats') { // adjust if your section id differs
            return;
        }

        // Only on GET (view), not POST (save)
        if (strtoupper($this->request->getMethod()) !== 'GET') {
            return;
        }

        try {
            // 1) Refresh version + news info
            $this->apiClient->syncUpdateInfo();

            // 2) Refresh license validity + last verify response
            $this->apiClient->syncVerifyInfo();
        } catch (\Throwable $e) {
            // Don’t break the config page if license server is down
            $this->logger->error(
                'Jscriptz_Subcats: failed to refresh license info on config load: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
