<?php
/**
 * Jscriptz Subcats
 *
 * @category Jscriptz
 * @package  Jscriptz_Subcats
 * @author   JScriptz <support@jscriptz.com>
 * @license  https://jscriptz.com/license Proprietary License
 * @link     https://jscriptz.com
 */
declare(strict_types=1);

namespace Jscriptz\Subcats\Test\Unit\Cron;

use Jscriptz\Subcats\Cron\LicenseSync;
use Jscriptz\Subcats\Model\License\ApiClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for LicenseSync cron
 *
 * @license  https://jscriptz.com/license Proprietary License
 * @link     https://jscriptz.com
 */
class LicenseSyncTest extends TestCase
{
    /**
     * Api client mock
     *
     * @var ApiClient&MockObject
     */
    private $_apiClient;

    /**
     * Logger mock
     *
     * @var LoggerInterface&MockObject
     */
    private $_logger;

    /**
     * Cron instance
     *
     * @var LicenseSync
     */
    private $_cron;

    /**
     * Set up test dependencies
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->_apiClient = $this->createMock(ApiClient::class);
        $this->_logger = $this->createMock(LoggerInterface::class);

        $this->_cron = new LicenseSync($this->_apiClient, $this->_logger);
    }

    /**
     * Test that execute calls both sync methods
     *
     * @return void
     */
    public function testExecuteCallsBothSyncMethods(): void
    {
        $this->_apiClient->expects($this->once())->method('syncUpdateInfo')->with();
        $this->_apiClient->expects($this->once())->method('syncVerifyInfo')->with();

        $this->_logger->expects($this->never())->method('error');

        $this->_cron->execute();
    }

    /**
     * Test that execute logs error when exception is thrown
     *
     * @return void
     */
    public function testExecuteLogsErrorWhenExceptionThrown(): void
    {
        $this->_apiClient->expects($this->once())
            ->method('syncUpdateInfo')
            ->willThrowException(new \RuntimeException('boom'));

        $this->_apiClient->expects($this->never())->method('syncVerifyInfo');

        $this->_logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('license cron failed: boom'),
                $this->arrayHasKey('exception')
            );

        $this->_cron->execute();
    }
}
