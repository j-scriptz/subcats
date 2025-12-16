<?php
declare(strict_types=1);

/**
 * Jscriptz LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.
 * It is also available through the web at this URL:
 * https://mage.jscriptz.com/LICENSE.txt
 *
 ********************************************************************
 *
 * PHP Version 8+
 *
 * @category  Jscriptz
 * @package   Jscriptz_Subcats
 * @author    Jason Lotzer <jasonlotzer@gmail.com>
 * @copyright 2019 - 2025 Jscriptz LLC
 * @license   https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link      https://mage.jscriptz.com
 */

namespace Jscriptz\Subcats\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Helper Image
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Custom directory relative to the "media" folder.
     */
    public const DIRECTORY = 'catalog/category';

    /**
     * Media directory instance
     *
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * Image factory instance
     *
     * @var \Magento\Framework\Image\Factory
     */
    protected $imageFactory;

    /**
     * Store manager instance
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context      $context      Helper context
     * @param \Magento\Framework\Filesystem              $filesystem   Filesystem instance
     * @param \Magento\Framework\Image\AdapterFactory    $imageFactory Image factory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Store manager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->imageFactory = $imageFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * First check this file on FS
     *
     * @param string $filename File path to check
     *
     * @return bool
     */
    protected function fileExists($filename)
    {
        if ($this->mediaDirectory->isFile($filename)) {
            return true;
        }
        return false;
    }

    /**
     * Resize image.
     *
     * @param string   $image  Image filename
     * @param int|null $width  Width in pixels
     * @param int|null $height Height in pixels
     *
     * @return string
     */
    public function resize($image, $width = null, $height = null)
    {
        $mediaFolder = self::DIRECTORY;

        $path = $mediaFolder . '/cache';
        if ($width !== null) {
            $path .= '/' . $width . 'x';
            if ($height !== null) {
                $path .= $height;
            }
        }

        $absolutePath = $this->mediaDirectory->getAbsolutePath($mediaFolder) . $image;
        $imageResized = $this->mediaDirectory->getAbsolutePath($path) . $image;

        if (!$this->fileExists($path . $image) && $this->fileExists($absolutePath)) {
            $imageFactory = $this->imageFactory->create();
            $imageFactory->open($absolutePath);
            $imageFactory->save($imageResized);
        }

        $baseUrl = $this->storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        return $baseUrl . $path . $image;
    }
}
