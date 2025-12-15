<?php
declare(strict_types=1);

namespace Jscriptz\Subcats\Test\Unit\Helper;

use Jscriptz\Subcats\Helper\Image as ImageHelper;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\Image\Adapter\AbstractAdapter;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * Set a protected property on an object (used to bypass the helper Context constructor).
     *
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    private function setProtectedProperty(object $object, string $property, $value): void
    {
        $ref = new \ReflectionClass($object);
        while ($ref && !$ref->hasProperty($property)) {
            $ref = $ref->getParentClass();
        }
        $this->assertNotFalse($ref, 'Property not found: ' . $property);

        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    public function testResizeBuildsExpectedCachePathAndReturnsMediaUrl(): void
    {
        /** @var WriteInterface&MockObject $mediaDir */
        $mediaDir = $this->createMock(WriteInterface::class);

        $mediaDir->method('getAbsolutePath')->willReturnCallback(function (string $path): string {
            // Note: Magento usually returns a trailing slash here.
            return '/pub/media/' . trim($path, '/') . '/';
        });

        // cache doesn't exist, original exists -> should resize
        $mediaDir->method('isFile')->willReturnCallback(function (string $filename): bool {
            if (strpos($filename, 'catalog/category/cache/100x200/') !== false) {
                return false;
            }
            if (strpos($filename, '/pub/media/catalog/category/') !== false) {
                return true;
            }
            return false;
        });

        /** @var AbstractAdapter&MockObject $adapter */
        $adapter = $this->createMock(AbstractAdapter::class);
        $adapter->expects($this->once())->method('open')->with('/pub/media/catalog/category//c/a/cat.jpg');
        $adapter->expects($this->once())->method('save')->with('/pub/media/catalog/category/cache/100x200//c/a/cat.jpg');

        /** @var AdapterFactory&MockObject $imageFactory */
        $imageFactory = $this->createMock(AdapterFactory::class);
        $imageFactory->expects($this->once())->method('create')->willReturn($adapter);

        /** @var StoreInterface&MockObject $store */
        $store = $this->createMock(StoreInterface::class);
        $store->method('getBaseUrl')->willReturn('https://example.com/media/');

        /** @var StoreManagerInterface&MockObject $storeManager */
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        /** @var ImageHelper&MockObject $helper */
        $helper = $this->getMockBuilder(ImageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProtectedProperty($helper, '_mediaDirectory', $mediaDir);
        $this->setProtectedProperty($helper, '_imageFactory', $imageFactory);
        $this->setProtectedProperty($helper, '_storeManager', $storeManager);

        $url = $helper->resize('/c/a/cat.jpg', 100, 200);

        $this->assertSame(
            'https://example.com/media/catalog/category/cache/100x200/c/a/cat.jpg',
            $url
        );
    }

    public function testResizeDoesNotRecreateWhenCachedFileExists(): void
    {
        /** @var WriteInterface&MockObject $mediaDir */
        $mediaDir = $this->createMock(WriteInterface::class);

        $mediaDir->method('getAbsolutePath')->willReturnCallback(function (string $path): string {
            return '/pub/media/' . trim($path, '/') . '/';
        });

        // cache exists -> should NOT call imageFactory
        $mediaDir->method('isFile')->willReturnCallback(function (string $filename): bool {
            if (strpos($filename, 'catalog/category/cache/') !== false) {
                return true;
            }
            return true;
        });

        /** @var AdapterFactory&MockObject $imageFactory */
        $imageFactory = $this->createMock(AdapterFactory::class);
        $imageFactory->expects($this->never())->method('create');

        /** @var StoreInterface&MockObject $store */
        $store = $this->createMock(StoreInterface::class);
        $store->method('getBaseUrl')->willReturn('https://example.com/media/');

        /** @var StoreManagerInterface&MockObject $storeManager */
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        /** @var ImageHelper&MockObject $helper */
        $helper = $this->getMockBuilder(ImageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->setProtectedProperty($helper, '_mediaDirectory', $mediaDir);
        $this->setProtectedProperty($helper, '_imageFactory', $imageFactory);
        $this->setProtectedProperty($helper, '_storeManager', $storeManager);

        $url = $helper->resize('/foo.jpg', null, null);

        $this->assertSame(
            'https://example.com/media/catalog/category/cache/foo.jpg',
            $url
        );
    }
}
