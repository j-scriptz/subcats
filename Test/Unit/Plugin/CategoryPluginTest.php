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

namespace Jscriptz\Subcats\Test\Unit\Plugin;

use Jscriptz\Subcats\Helper\Data as DataHelper;
use Jscriptz\Subcats\Plugin\CategoryPlugin;
use Magento\Catalog\Model\Category;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for CategoryPlugin
 *
 * @license  https://jscriptz.com/license Proprietary License
 * @link     https://jscriptz.com
 */
class CategoryPluginTest extends TestCase
{
    /**
     * Data helper mock
     *
     * @var DataHelper&MockObject
     */
    private $_helper;

    /**
     * Store manager mock
     *
     * @var StoreManagerInterface&MockObject
     */
    private $_storeManager;

    /**
     * Category plugin instance
     *
     * @var CategoryPlugin
     */
    private $_plugin;

    /**
     * Set up test dependencies
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->_helper = $this->createMock(DataHelper::class);
        $this->_storeManager = $this->createMock(
            StoreManagerInterface::class
        );

        $this->_plugin = new CategoryPlugin(
            $this->_storeManager,
            $this->_helper
        );
    }

    /**
     * Test aroundGetData returns URL for subcat image key
     *
     * @return void
     */
    public function testAroundGetDataReturnsUrlForSubcatImageKey(): void
    {
        $subject = $this->createMock(Category::class);

        $proceed = function (string $key, $index = null) {
            $this->assertSame(DataHelper::ATTRIBUTE_NAME, $key);
            $this->assertNull($index);
            return 'foo.jpg';
        };

        $this->_helper->expects($this->once())
            ->method('getUrl')
            ->with('foo.jpg')
            ->willReturn(
                'https://example.com/media/catalog/category/foo.jpg'
            );

        $result = $this->_plugin->aroundGetData(
            $subject,
            $proceed,
            DataHelper::ATTRIBUTE_NAME,
            null
        );

        $this->assertSame(
            'https://example.com/media/catalog/category/foo.jpg',
            $result
        );
    }

    /**
     * Test aroundGetData returns empty value when proceed returns empty
     *
     * @return void
     */
    public function testAroundGetDataReturnsEmptyValueWhenProceedReturnsEmpty(): void
    {
        $subject = $this->createMock(Category::class);

        $proceed = function (string $key, $index = null) {
            return '';
        };

        $this->_helper->expects($this->never())->method('getUrl');

        $result = $this->_plugin->aroundGetData(
            $subject,
            $proceed,
            DataHelper::ATTRIBUTE_NAME,
            null
        );

        $this->assertSame('', $result);
    }

    /**
     * Test aroundGetData for other keys just proceeds
     *
     * @return void
     */
    public function testAroundGetDataForOtherKeysJustProceeds(): void
    {
        $subject = $this->createMock(Category::class);

        $proceed = function (string $key, $index = null) {
            $this->assertSame('other_key', $key);
            return 123;
        };

        $this->_helper->expects($this->never())->method('getUrl');

        $result = $this->_plugin->aroundGetData(
            $subject,
            $proceed,
            'other_key',
            null
        );

        $this->assertSame(123, $result);
    }
}
