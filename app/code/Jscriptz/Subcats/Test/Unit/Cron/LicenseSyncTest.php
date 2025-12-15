<?php
declare(strict_types=1);

namespace Jscriptz\Subcats\Test\Unit\Cron;

use Jscriptz\Subcats\Cron\LicenseSync;
use Jscriptz\Subcats\Model\License\ApiClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LicenseSyncTest extends TestCase
{
    /** @var ApiClient&MockObject */
    private $apiClient;

    /** @var LoggerInterface&MockObject */
    private $logger;

    /** @var LicenseSync */
    private $cron;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->cron = new LicenseSync($this->apiClient, $this->logger);
    }

    public function testExecuteCallsBothSyncMethods(): void
    {
        $this->apiClient->expects($this->once())->method('syncUpdateInfo')->with();
        $this->apiClient->expects($this->once())->method('syncVerifyInfo')->with();

        $this->logger->expects($this->never())->method('error');

        $this->cron->execute();
    }

    public function testExecuteLogsErrorWhenExceptionThrown(): void
    {
        $this->apiClient->expects($this->once())
            ->method('syncUpdateInfo')
            ->willThrowException(new \RuntimeException('boom'));

        $this->apiClient->expects($this->never())->method('syncVerifyInfo');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('license cron failed: boom'),
                $this->arrayHasKey('exception')
            );

        $this->cron->execute();
    }
}
