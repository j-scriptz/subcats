<?php
declare(strict_types=1);

namespace Jscriptz\Subcats\Test\Unit\Plugin;

use Jscriptz\Subcats\Helper\Data as DataHelper;
use Jscriptz\Subcats\Plugin\DataProviderPlugin;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\DataProvider as Subject;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderPluginTest extends TestCase
{
    /** @var StoreManagerInterface&MockObject */
    private $storeManager;

    /** @var DataHelper&MockObject */
    private $helper;

    /** @var DataProviderPlugin */
    private $plugin;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->helper = $this->createMock(DataHelper::class);

        $this->plugin = new DataProviderPlugin($this->storeManager, $this->helper);
    }

    public function testAroundGetDataNormalizesFullMediaUrlToFilename(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(42);

        $category->method('getData')->willReturnCallback(function ($key = null) {
            if ($key === null) {
                return [DataHelper::ATTRIBUTE_NAME => 'https://example.com/media/catalog/category/foo.jpg'];
            }
            if ($key === DataHelper::ATTRIBUTE_NAME) {
                return 'https://example.com/media/catalog/category/foo.jpg';
            }
            if ($key === DataHelper::ATTRIBUTE_LEGACY_NAME) {
                return null;
            }
            return null;
        });

        $subject = $this->createMock(Subject::class);
        $subject->method('getCurrentCategory')->willReturn($category);

        $this->helper->expects($this->once())
            ->method('getImageUrl')
            ->with($category)
            ->willReturn('https://example.com/media/catalog/category/foo.jpg');

        $proceed = function (): array {
            return [42 => []];
        };

        $result = $this->plugin->aroundGetData($subject, $proceed);

        $this->assertArrayHasKey(42, $result);
        $this->assertArrayHasKey(DataHelper::ATTRIBUTE_NAME, $result[42]);

        $payload = $result[42][DataHelper::ATTRIBUTE_NAME][0];
        $this->assertSame('foo.jpg', $payload['name']);
        $this->assertSame('https://example.com/media/catalog/category/foo.jpg', $payload['url']);
    }

    public function testAroundGetDataFallsBackToLegacyAttribute(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(7);

        $category->method('getData')->willReturnCallback(function ($key = null) {
            if ($key === null) {
                return [DataHelper::ATTRIBUTE_NAME => '']; // key exists, but empty -> triggers processing
            }
            if ($key === DataHelper::ATTRIBUTE_NAME) {
                return '';
            }
            if ($key === DataHelper::ATTRIBUTE_LEGACY_NAME) {
                return 'media/catalog/category/legacy.png';
            }
            return null;
        });

        $subject = $this->createMock(Subject::class);
        $subject->method('getCurrentCategory')->willReturn($category);

        $this->helper->expects($this->once())
            ->method('getImageUrl')
            ->with($category)
            ->willReturn('https://example.com/media/catalog/category/legacy.png');

        $proceed = function (): array {
            return [7 => []];
        };

        $result = $this->plugin->aroundGetData($subject, $proceed);

        $payload = $result[7][DataHelper::ATTRIBUTE_NAME][0];
        $this->assertSame('legacy.png', $payload['name']);
    }

    public function testAroundGetDataDoesNothingWhenNoCurrentCategory(): void
    {
        $subject = $this->createMock(Subject::class);
        $subject->method('getCurrentCategory')->willReturn(null);

        $this->helper->expects($this->never())->method('getImageUrl');

        $proceed = function (): array {
            return ['ok' => true];
        };

        $result = $this->plugin->aroundGetData($subject, $proceed);

        $this->assertSame(['ok' => true], $result);
    }
}
