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
use Jscriptz\Subcats\Plugin\DataProviderPlugin;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\DataProvider as Subject;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for DataProviderPlugin
 *
 * @license  https://jscriptz.com/license Proprietary License
 * @link     https://jscriptz.com
 */
class DataProviderPluginTest extends TestCase
{
    /**
     * Store manager mock
     *
     * @var StoreManagerInterface&MockObject
     */
    private $_storeManager;

    /**
     * Data helper mock
     *
     * @var DataHelper&MockObject
     */
    private $_helper;

    /**
     * Data provider plugin instance
     *
     * @var DataProviderPlugin
     */
    private $_plugin;

    /**
     * Set up test dependencies
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->_storeManager = $this->createMock(
            StoreManagerInterface::class
        );
        $this->_helper = $this->createMock(DataHelper::class);

        $this->_plugin = new DataProviderPlugin(
            $this->_storeManager,
            $this->_helper
        );
    }

    /**
     * Test aroundGetData normalizes full media URL to filename
     *
     * @return void
     */
    public function testAroundGetDataNormalizesFullMediaUrlToFilename(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(42);

        $category->method('getData')->willReturnCallback(
            function ($key = '') {
                // Magento calls getData() with an empty string
                // when no key is passed.
                if ($key === null || $key === '') {
                    return [
                        DataHelper::ATTRIBUTE_NAME =>
                            'https://example.com/media/catalog/' .
                            'category/foo.jpg'
                    ];
                }
                if ($key === DataHelper::ATTRIBUTE_NAME) {
                    return 'https://example.com/media/catalog/' .
                        'category/foo.jpg';
                }
                if ($key === DataHelper::ATTRIBUTE_LEGACY_NAME) {
                    return null;
                }
                return null;
            }
        );

        $subject = $this->createMock(Subject::class);
        $subject->method('getCurrentCategory')->willReturn($category);

        $this->_helper->expects($this->once())
            ->method('getImageUrl')
            ->with($category)
            ->willReturn(
                'https://example.com/media/catalog/category/foo.jpg'
            );

        $proceed = function (): array {
            return [42 => []];
        };

        $result = $this->_plugin->aroundGetData($subject, $proceed);

        $this->assertArrayHasKey(42, $result);
        $this->assertArrayHasKey(
            DataHelper::ATTRIBUTE_NAME,
            $result[42]
        );

        $payload = $result[42][DataHelper::ATTRIBUTE_NAME][0];
        $this->assertSame('foo.jpg', $payload['name']);
        $this->assertSame(
            'https://example.com/media/catalog/category/foo.jpg',
            $payload['url']
        );
    }

    /**
     * Test aroundGetData falls back to legacy attribute
     *
     * @return void
     */
    public function testAroundGetDataFallsBackToLegacyAttribute(): void
    {
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(7);

        $category->method('getData')->willReturnCallback(
            function ($key = '') {
                if ($key === null || $key === '') {
                    // key exists, but empty -> triggers processing
                    return [DataHelper::ATTRIBUTE_NAME => ''];
                }
                if ($key === DataHelper::ATTRIBUTE_NAME) {
                    return '';
                }
                if ($key === DataHelper::ATTRIBUTE_LEGACY_NAME) {
                    return 'media/catalog/category/legacy.png';
                }
                return null;
            }
        );

        $subject = $this->createMock(Subject::class);
        $subject->method('getCurrentCategory')->willReturn($category);

        $this->_helper->expects($this->once())
            ->method('getImageUrl')
            ->with($category)
            ->willReturn(
                'https://example.com/media/catalog/category/legacy.png'
            );

        $proceed = function (): array {
            return [7 => []];
        };

        $result = $this->_plugin->aroundGetData($subject, $proceed);

        $payload = $result[7][DataHelper::ATTRIBUTE_NAME][0];
        $this->assertSame('legacy.png', $payload['name']);
    }

    /**
     * Test aroundGetData does nothing when no current category
     *
     * @return void
     */
    public function testAroundGetDataDoesNothingWhenNoCurrentCategory(): void
    {
        $subject = $this->createMock(Subject::class);
        $subject->method('getCurrentCategory')->willReturn(null);

        $this->_helper->expects($this->never())->method('getImageUrl');

        $proceed = function (): array {
            return ['ok' => true];
        };

        $result = $this->_plugin->aroundGetData($subject, $proceed);

        $this->assertSame(['ok' => true], $result);
    }
}
