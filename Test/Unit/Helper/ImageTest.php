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

namespace Jscriptz\Subcats\Test\Unit\Helper;

use Jscriptz\Subcats\Helper\Image as ImageHelper;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\Image\Adapter\AbstractAdapter;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Image helper
 *
 * @license  https://jscriptz.com/license Proprietary License
 * @link     https://jscriptz.com
 */
class ImageTest extends TestCase
{
    /**
     * Set a protected property on an object
     *
     * Used to bypass the helper Context constructor.
     *
     * @param object $object   The object to modify
     * @param string $property The property name
     * @param mixed  $value    The value to set
     *
     * @return void
     */
    private function _setProtectedProperty(
        object $object,
        string $property,
        $value
    ): void {
        $ref = new \ReflectionClass($object);
        while ($ref && !$ref->hasProperty($property)) {
            $ref = $ref->getParentClass();
        }
        $this->assertNotFalse($ref, 'Property not found: ' . $property);

        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    /**
     * Test resize builds expected cache path and returns media URL
     *
     * @return void
     */
    public function testResizeBuildsExpectedCachePathAndReturnsMediaUrl(): void
    {
        /**
         * Media directory mock
         *
         * @var WriteInterface&MockObject $mediaDir
         */
        $mediaDir = $this->createMock(WriteInterface::class);

        $mediaDir->method('getAbsolutePath')->willReturnCallback(
            function (string $path): string {
                // Note: Magento usually returns a trailing slash here.
                return '/pub/media/' . trim($path, '/') . '/';
            }
        );

        // cache doesn't exist, original exists -> should resize
        $mediaDir->method('isFile')->willReturnCallback(
            function (string $filename): bool {
                $cachePattern = 'catalog/category/cache/100x200/';
                if (strpos($filename, $cachePattern) !== false) {
                    return false;
                }
                $mediaPattern = '/pub/media/catalog/category/';
                if (strpos($filename, $mediaPattern) !== false) {
                    return true;
                }
                return false;
            }
        );

        /**
         * Image adapter mock
         *
         * @var AbstractAdapter&MockObject $adapter
         */
        $adapter = $this->createMock(AbstractAdapter::class);
        $adapter->expects($this->once())->method('open')
            ->with('/pub/media/catalog/category//c/a/cat.jpg');
        $adapter->expects($this->once())->method('save')
            ->with('/pub/media/catalog/category/cache/100x200//c/a/cat.jpg');

        /**
         * Image factory mock
         *
         * @var AdapterFactory&MockObject $imageFactory
         */
        $imageFactory = $this->createMock(AdapterFactory::class);
        $imageFactory->expects($this->once())->method('create')
            ->willReturn($adapter);

        /**
         * Store mock
         *
         * @var Store&MockObject $store
         */
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')
            ->willReturn('https://example.com/media/');

        /**
         * Store manager mock
         *
         * @var StoreManagerInterface&MockObject $storeManager
         */
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        // Important: do NOT create a PHPUnit mock for the helper itself.
        // A full mock will stub all methods (including resize()) and
        // return null.
        /**
         * Image helper instance
         *
         * @var ImageHelper $helper
         */
        $helperClass = new \ReflectionClass(ImageHelper::class);
        $helper = $helperClass->newInstanceWithoutConstructor();

        $this->_setProtectedProperty($helper, '_mediaDirectory', $mediaDir);
        $this->_setProtectedProperty($helper, '_imageFactory', $imageFactory);
        $this->_setProtectedProperty(
            $helper,
            '_storeManager',
            $storeManager
        );

        $url = $helper->resize('/c/a/cat.jpg', 100, 200);

        $this->assertSame(
            'https://example.com/media/catalog/category/cache/100x200/c/a/cat.jpg',
            $url
        );
    }

    /**
     * Test resize does not recreate when cached file exists
     *
     * @return void
     */
    public function testResizeDoesNotRecreateWhenCachedFileExists(): void
    {
        /**
         * Media directory mock
         *
         * @var WriteInterface&MockObject $mediaDir
         */
        $mediaDir = $this->createMock(WriteInterface::class);

        $mediaDir->method('getAbsolutePath')->willReturnCallback(
            function (string $path): string {
                return '/pub/media/' . trim($path, '/') . '/';
            }
        );

        // cache exists -> should NOT call imageFactory
        $mediaDir->method('isFile')->willReturnCallback(
            function (string $filename): bool {
                $cachePattern = 'catalog/category/cache/';
                if (strpos($filename, $cachePattern) !== false) {
                    return true;
                }
                return true;
            }
        );

        /**
         * Image factory mock
         *
         * @var AdapterFactory&MockObject $imageFactory
         */
        $imageFactory = $this->createMock(AdapterFactory::class);
        $imageFactory->expects($this->never())->method('create');

        /**
         * Store mock
         *
         * @var Store&MockObject $store
         */
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')
            ->willReturn('https://example.com/media/');

        /**
         * Store manager mock
         *
         * @var StoreManagerInterface&MockObject $storeManager
         */
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        /**
         * Image helper instance
         *
         * @var ImageHelper $helper
         */
        $helper = (new \ReflectionClass(ImageHelper::class))
            ->newInstanceWithoutConstructor();

        $this->_setProtectedProperty(
            $helper,
            '_mediaDirectory',
            $mediaDir
        );
        $this->_setProtectedProperty(
            $helper,
            '_imageFactory',
            $imageFactory
        );
        $this->_setProtectedProperty(
            $helper,
            '_storeManager',
            $storeManager
        );

        $url = $helper->resize('/foo.jpg', null, null);

        $this->assertSame(
            'https://example.com/media/catalog/category/cache/foo.jpg',
            $url
        );
    }
}
