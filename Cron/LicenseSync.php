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


namespace Jscriptz\Subcats\Cron;

use Jscriptz\Subcats\Model\License\ApiClient;
use Psr\Log\LoggerInterface;

/**
 * License sync cron job.
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class LicenseSync
{
    /**
     * API client instance.
     *
     * @var ApiClient
     */
    private $_apiClient;

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param ApiClient       $apiClient API client instance
     * @param LoggerInterface $logger    Logger instance
     */
    public function __construct(
        ApiClient $apiClient,
        LoggerInterface $logger
    ) {
        $this->_apiClient = $apiClient;
        $this->_logger    = $logger;
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
            $this->_apiClient->syncUpdateInfo();

            // Second API: trial / license header / whatever your second endpoint returns
            $this->_apiClient->syncVerifyInfo();
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Jscriptz_Subcats: license cron failed: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
