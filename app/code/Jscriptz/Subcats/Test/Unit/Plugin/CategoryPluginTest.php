<?php
declare(strict_types=1);

namespace Jscriptz\Subcats\Test\Unit\Plugin;

use Jscriptz\Subcats\Helper\Data as DataHelper;
use Jscriptz\Subcats\Plugin\CategoryPlugin;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoryPluginTest extends TestCase
{
    /** @var DataHelper&MockObject */
    private $helper;

    /** @var StoreManagerInterface&MockObject */
    private $storeManager;

    /** @var CategoryPlugin */
    private $plugin;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(DataHelper::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->plugin = new CategoryPlugin($this->storeManager, $this->helper);
    }

    public function testAroundGetDataReturnsUrlForSubcatImageKey(): void
    {
        $subject = new \stdClass();

        $proceed = function (string $key, $index = null) {
            $this->assertSame(DataHelper::ATTRIBUTE_NAME, $key);
            $this->assertNull($index);
            return 'foo.jpg';
        };

        $this->helper->expects($this->once())
            ->method('getUrl')
            ->with('foo.jpg')
            ->willReturn('https://example.com/media/catalog/category/foo.jpg');

        $result = $this->plugin->aroundGetData($subject, $proceed, DataHelper::ATTRIBUTE_NAME, null);

        $this->assertSame('https://example.com/media/catalog/category/foo.jpg', $result);
    }

    public function testAroundGetDataReturnsEmptyValueWhenProceedReturnsEmpty(): void
    {
        $subject = new \stdClass();

        $proceed = function (string $key, $index = null) {
            return '';
        };

        $this->helper->expects($this->never())->method('getUrl');

        $result = $this->plugin->aroundGetData($subject, $proceed, DataHelper::ATTRIBUTE_NAME, null);

        $this->assertSame('', $result);
    }

    public function testAroundGetDataForOtherKeysJustProceeds(): void
    {
        $subject = new \stdClass();

        $proceed = function (string $key, $index = null) {
            $this->assertSame('other_key', $key);
            return 123;
        };

        $this->helper->expects($this->never())->method('getUrl');

        $result = $this->plugin->aroundGetData($subject, $proceed, 'other_key', null);

        $this->assertSame(123, $result);
    }
}
