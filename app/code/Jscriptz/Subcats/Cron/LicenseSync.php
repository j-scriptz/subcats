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


namespace Jscriptz\Subcats\Cron;

use Jscriptz\Subcats\Model\License\ApiClient;
use Psr\Log\LoggerInterface;

class LicenseSync
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ApiClient $apiClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        ApiClient $apiClient,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->logger    = $logger;
    }

    /**
     * Execute.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            // First API: update/version/news info
            $this->apiClient->syncUpdateInfo();

            // Second API: trial / license header / whatever your second endpoint returns
            $this->apiClient->syncVerifyInfo();
        } catch (\Throwable $e) {
            $this->logger->error(
                'Jscriptz_Subcats: license cron failed: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
