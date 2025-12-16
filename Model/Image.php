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


namespace Jscriptz\Subcats\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Image as MagentoImage;
use Magento\Framework\Image\Factory as MagentoImageFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\FileSystem as ViewFileSystem;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Image model for handling image operations.
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @method                                           string getFile()
 * @method                                           string getLabel()
 * @method                                           string getPosition()
 */
class Image extends AbstractModel
{
    /**
     * Image width
     *
     * @var int
     */
    protected $width;

    /**
     * Image height
     *
     * @var int
     */
    protected $height;

    /**
     * Default quality value (for JPEG images only).
     *
     * @var int
     */
    protected $quality = 100;

    /**
     * Keep aspect ratio flag
     *
     * @var bool
     */
    protected $keepAspectRatio = true;

    /**
     * Keep frame flag
     *
     * @var bool
     */
    protected $keepFrame = true;

    /**
     * Keep transparency flag
     *
     * @var bool
     */
    protected $keepTransparency = true;

    /**
     * Constrain only flag
     *
     * @var bool
     */
    protected $constrainOnly = true;

    /**
     * Background color RGB values
     *
     * @var int[]
     */
    protected $backgroundColor = [255, 255, 255];

    /**
     * Base file path
     *
     * @var string
     */
    protected $baseFile;

    /**
     * Is base file placeholder flag
     *
     * @var bool
     */
    protected $isBaseFilePlaceholder;

    /**
     * New file path
     *
     * @var string|bool
     */
    protected $newFile;

    /**
     * Image processor instance
     *
     * @var MagentoImage
     */
    protected $processor;

    /**
     * Destination subdirectory
     *
     * @var string
     */
    protected $destinationSubdir;

    /**
     * Rotation angle
     *
     * @var int
     */
    protected $angle;

    /**
     * Watermark file path
     *
     * @var string
     */
    protected $watermarkFile;

    /**
     * Watermark position
     *
     * @var int
     */
    protected $watermarkPosition;

    /**
     * Watermark width
     *
     * @var int
     */
    protected $watermarkWidth;

    /**
     * Watermark height
     *
     * @var int
     */
    protected $watermarkHeight;

    /**
     * Watermark image opacity
     *
     * @var int
     */
    protected $watermarkImageOpacity = 70;

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
     * Asset repository instance
     *
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * View file system instance
     *
     * @var \Magento\Framework\View\FileSystem
     */
    protected $viewFileSystem;

    /**
     * Core file storage database helper
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $coreFileStorageDatabase = null;

    /**
     * Scope config instance
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Uploader instance
     *
     * @var Uploader
     */
    protected $uploader;

    /**
     * Store manager instance
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Entity code
     *
     * @var string
     */
    protected $entityCode;

    /**
     * Constructor
     *
     * @param Context               $context                 Application context
     * @param Registry              $registry                Registry instance
     * @param StoreManagerInterface $storeManager            Store manager instance
     * @param Uploader              $uploader                Uploader instance
     * @param Database              $coreFileStorageDatabase Database storage helper
     * @param Filesystem            $filesystem              Filesystem instance
     * @param MagentoImageFactory   $imageFactory            Image factory
     * @param Repository            $assetRepo               Asset repository
     * @param ViewFileSystem        $viewFileSystem          View filesystem
     * @param ScopeConfigInterface  $scopeConfig             Scope config
     * @param string                $entityCode              Entity code
     * @param AbstractResource|null $resource                Resource model
     * @param AbstractDb|null       $resourceCollection      Resource collection
     * @param array                 $data                    Additional data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Uploader $uploader,
        Database $coreFileStorageDatabase,
        Filesystem $filesystem,
        MagentoImageFactory $imageFactory,
        Repository $assetRepo,
        ViewFileSystem $viewFileSystem,
        ScopeConfigInterface $scopeConfig,
        $entityCode,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager             = $storeManager;
        $this->uploader                 = $uploader;
        $this->coreFileStorageDatabase  = $coreFileStorageDatabase;
        $this->imageFactory             = $imageFactory;
        $this->assetRepo                = $assetRepo;
        $this->viewFileSystem           = $viewFileSystem;
        $this->scopeConfig              = $scopeConfig;
        $this->entityCode               = $entityCode;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->mediaDirectory->create($this->uploader->getBasePath());
    }

    /**
     * Set image width
     *
     * @param int $width Image width in pixels
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Get image width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set image height
     *
     * @param int $height Image height in pixels
     *
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Get image height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set image quality, values in percentage from 0 to 100
     *
     * @param int $quality Image quality (0-100)
     *
     * @return $this
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
        return $this;
    }

    /**
     * Get image quality
     *
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * Set whether to keep aspect ratio when resizing
     *
     * @param bool $keep Whether to keep aspect ratio
     *
     * @return $this
     */
    public function setKeepAspectRatio($keep)
    {
        $this->keepAspectRatio = (bool)$keep;
        return $this;
    }

    /**
     * Set whether to keep frame when resizing
     *
     * @param bool $keep Whether to keep frame
     *
     * @return $this
     */
    public function setKeepFrame($keep)
    {
        $this->keepFrame = (bool)$keep;
        return $this;
    }

    /**
     * Set whether to keep transparency when resizing
     *
     * @param bool $keep Whether to keep transparency
     *
     * @return $this
     */
    public function setKeepTransparency($keep)
    {
        $this->keepTransparency = (bool)$keep;
        return $this;
    }

    /**
     * Set whether to constrain only larger images when resizing
     *
     * @param bool $flag Whether to constrain only larger images
     *
     * @return $this
     */
    public function setConstrainOnly($flag)
    {
        $this->constrainOnly = (bool)$flag;
        return $this;
    }

    /**
     * Set background color for image
     *
     * @param int[] $rgbArray RGB color array [r, g, b]
     *
     * @return $this
     */
    public function setBackgroundColor(array $rgbArray)
    {
        $this->backgroundColor = $rgbArray;
        return $this;
    }

    /**
     * Set image dimensions from a size string (e.g., "100x200")
     *
     * @param string $size Size string in format "widthxheight"
     *
     * @return $this
     */
    public function setSize($size)
    {
        // determine width and height from string
        list($width, $height) = explode('x', strtolower($size), 2);
        foreach (['width', 'height'] as $wh) {
            ${$wh} = (int)${$wh};
            if (empty(${$wh})) {
                ${$wh} = null;
            }
        }

        // set sizes
        $this->setWidth($width)->setHeight($height);

        return $this;
    }

    /**
     * Check if there is enough memory to process the image file
     *
     * @param string|null $file File path to check memory for
     *
     * @return bool
     */
    protected function checkMemory($file = null)
    {
        $memoryLimit = $this->getMemoryLimit();
        $memoryUsage = $this->getMemoryUsage();
        $needMemory = $this->getNeedMemoryForFile($file);
        return $memoryLimit > $memoryUsage + $needMemory
            || $memoryLimit == -1;
    }

    /**
     * Get PHP memory limit in bytes
     *
     * @return string
     */
    protected function getMemoryLimit()
    {
        $memoryLimit = trim(strtoupper(ini_get('memory_limit')));

        if (!isset($memoryLimit[0])) {
            $memoryLimit = "128M";
        }

        if (substr($memoryLimit, -1) == 'K') {
            return substr($memoryLimit, 0, -1) * 1024;
        }
        if (substr($memoryLimit, -1) == 'M') {
            return substr($memoryLimit, 0, -1) * 1024 * 1024;
        }
        if (substr($memoryLimit, -1) == 'G') {
            return substr($memoryLimit, 0, -1) * 1024 * 1024 * 1024;
        }
        return $memoryLimit;
    }

    /**
     * Get current memory usage in bytes
     *
     * @return int
     */
    protected function getMemoryUsage()
    {
        if (function_exists('memory_get_usage')) {
            return memory_get_usage();
        }
        return 0;
    }

    /**
     * Calculate memory needed to process the image file
     *
     * @param string|null $file File path to calculate memory for
     *
     * @return float|int
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getNeedMemoryForFile($file = null)
    {
        $file = $file === null ? $this->getBaseFile() : $file;
        if (!$file) {
            return 0;
        }

        if (!$this->mediaDirectory->isExist($file)) {
            return 0;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $imageInfo = getimagesize($this->mediaDirectory->getAbsolutePath($file));

        if (!isset($imageInfo[0]) || !isset($imageInfo[1])) {
            return 0;
        }
        if (!isset($imageInfo['channels'])) {
            // if there is no info about this parameter lets set it for maximum
            $imageInfo['channels'] = 4;
        }
        if (!isset($imageInfo['bits'])) {
            // if there is no info about this parameter lets set it for maximum
            $imageInfo['bits'] = 8;
        }
        $pixelSize = $imageInfo[0] * $imageInfo[1]
            * $imageInfo['bits'] * $imageInfo['channels'] / 8;
        return round(($pixelSize + Pow(2, 16)) * 1.65);
    }

    /**
     * Convert array of 3 items (decimal r, g, b) to string of their hex values
     *
     * @param int[] $rgbArray Array of RGB values [r, g, b]
     *
     * @return string
     */
    protected function rgbToString($rgbArray)
    {
        $result = [];
        foreach ($rgbArray as $value) {
            if (null === $value) {
                $result[] = 'null';
            } else {
                $result[] = sprintf('%02s', dechex($value));
            }
        }
        return implode($result);
    }

    /**
     * Set filenames for base file and new file
     *
     * @param string $file File path to set as base
     *
     * @return $this
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function setBaseFile($file)
    {
        $this->isBaseFilePlaceholder = false;

        if ($file && 0 !== strpos($file, '/', 0)) {
            $file = '/' . $file;
        }
        $baseDir = $this->uploader->getBasePath();

        if ($file) {
            $basePath = $baseDir . $file;
            if (!$this->fileExists($basePath) || !$this->checkMemory($basePath)) {
                $file = null;
            }
        }
        if (!$file) {
            $this->isBaseFilePlaceholder = true;
            $this->newFile = true;
            return $this;
        }

        $baseFile = $baseDir . $file;

        if (!$file || !$this->mediaDirectory->isFile($baseFile)) {
            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception(__('We can\'t find the image file.'));
        }

        $this->baseFile = $baseFile;

        // build new filename (most important params)
        $path = [
            $this->uploader->getBasePath(),
            'cache',
            $this->storeManager->getStore()->getId(),
            $path[] = $this->getDestinationSubdir(),
        ];
        if (!empty($this->width) || !empty($this->height)) {
            $path[] = "{$this->width}x{$this->height}";
        }

        // add misk params as a hash
        $miscParams = [
            ($this->keepAspectRatio ? '' : 'non') . 'proportional',
            ($this->keepFrame ? '' : 'no') . 'frame',
            ($this->keepTransparency ? '' : 'no') . 'transparency',
            ($this->constrainOnly ? 'do' : 'not') . 'constrainonly',
            $this->rgbToString($this->backgroundColor),
            'angle' . $this->angle,
            'quality' . $this->quality,
        ];

        // if has watermark add watermark params to hash
        if ($this->getWatermarkFile()) {
            $miscParams[] = $this->getWatermarkFile();
            $miscParams[] = $this->getWatermarkImageOpacity();
            $miscParams[] = $this->getWatermarkPosition();
            $miscParams[] = $this->getWatermarkWidth();
            $miscParams[] = $this->getWatermarkHeight();
        }

        $path[] = hash('sha256', implode('_', $miscParams));

        // append prepared filename
        $this->newFile = implode('/', $path) . $file;
        // the $file contains heading slash

        return $this;
    }

    /**
     * Get base file path
     *
     * @return string
     */
    public function getBaseFile()
    {
        return $this->baseFile;
    }

    /**
     * Get new file path
     *
     * @return bool|string
     */
    public function getNewFile()
    {
        return $this->newFile;
    }

    /**
     * Retrieve 'true' if image is a base file placeholder
     *
     * @return bool
     */
    public function isBaseFilePlaceholder()
    {
        return (bool)$this->isBaseFilePlaceholder;
    }

    /**
     * Set image processor.
     *
     * @param MagentoImage $processor Image processor instance
     *
     * @return $this
     */
    public function setImageProcessor($processor)
    {
        $this->processor = $processor;
        return $this;
    }

    /**
     * Get image processor.
     *
     * @return MagentoImage
     */
    public function getImageProcessor()
    {
        if (!$this->processor) {
            $filename = $this->getBaseFile()
                ? $this->mediaDirectory->getAbsolutePath($this->getBaseFile())
                : null;
            $this->processor = $this->imageFactory->create($filename);
        }
        $this->processor->keepAspectRatio($this->keepAspectRatio);
        $this->processor->keepFrame($this->keepFrame);
        $this->processor->keepTransparency($this->keepTransparency);
        $this->processor->constrainOnly($this->constrainOnly);
        $this->processor->backgroundColor($this->backgroundColor);
        $this->processor->quality($this->quality);
        return $this->processor;
    }

    /**
     * Resize image to configured dimensions.
     *
     * @see    \Magento\Framework\Image\Adapter\AbstractAdapter
     * @return $this
     */
    public function resize()
    {
        if ($this->getWidth() === null && $this->getHeight() === null) {
            return $this;
        }
        $this->getImageProcessor()->resize($this->width, $this->height);
        return $this;
    }

    /**
     * Rotate image by angle.
     *
     * @param int $angle Rotation angle in degrees
     *
     * @return $this
     */
    public function rotate($angle)
    {
        $angle = (int) ($angle);
        $this->getImageProcessor()->rotate($angle);
        return $this;
    }

    /**
     * Set angle for rotating
     *
     * This func actually affects only the cache filename.
     *
     * @param int $angle Rotation angle in degrees
     *
     * @return $this
     */
    public function setAngle($angle)
    {
        $this->angle = $angle;
        return $this;
    }

    /**
     * Add watermark to image. Size param in format 100x200.
     *
     * @param string $file     Watermark file path
     * @param string $position Watermark position
     * @param array  $size     Size array ['width' => int, 'height' => int]
     * @param int    $width    Watermark width
     * @param int    $height   Watermark height
     * @param int    $opacity  Watermark opacity
     *
     * @return $this
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function setWatermark(
        $file,
        $position = null,
        $size = null,
        $width = null,
        $height = null,
        $opacity = null
    ) {
        if ($this->isBaseFilePlaceholder) {
            return $this;
        }

        if ($file) {
            $this->setWatermarkFile($file);
        } else {
            return $this;
        }

        if ($position) {
            $this->setWatermarkPosition($position);
        }
        if ($size) {
            $this->setWatermarkSize($size);
        }
        if ($width) {
            $this->setWatermarkWidth($width);
        }
        if ($height) {
            $this->setWatermarkHeight($height);
        }
        if ($opacity) {
            $this->setWatermarkImageOpacity($opacity);
        }
        $filePath = $this->getWatermarkFilePath();

        if ($filePath) {
            $imagePreprocessor = $this->getImageProcessor();
            $imagePreprocessor->setWatermarkPosition(
                $this->getWatermarkPosition()
            );
            $imagePreprocessor->setWatermarkImageOpacity(
                $this->getWatermarkImageOpacity()
            );
            $imagePreprocessor->setWatermarkWidth($this->getWatermarkWidth());
            $imagePreprocessor->setWatermarkHeight(
                $this->getWatermarkHeight()
            );
            $imagePreprocessor->watermark($filePath);
        }

        return $this;
    }

    /**
     * Save processed image to file.
     *
     * @return $this
     */
    public function saveFile()
    {
        if ($this->isBaseFilePlaceholder && $this->newFile === true) {
            return $this;
        }
        $filename = $this->mediaDirectory->getAbsolutePath($this->getNewFile());
        $this->getImageProcessor()->save($filename);
        $this->coreFileStorageDatabase->saveFile($filename);
        return $this;
    }

    /**
     * Get image URL.
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->newFile === true) {
            $placeholderPath = "Sample_News::images/" . $this->entityCode
                . "/placeholder/{$this->getDestinationSubdir()}.jpg";
            $url = $this->assetRepo->getUrl($placeholderPath);
        } else {
            $url = $this->storeManager->getStore()->getBaseUrl(
                UrlInterface::URL_TYPE_MEDIA
            ) . $this->newFile;
        }

        return $url;
    }

    /**
     * Set destination subdirectory.
     *
     * @param string $dir Destination subdirectory path
     *
     * @return $this
     */
    public function setDestinationSubdir($dir)
    {
        $this->destinationSubdir = $dir;
        return $this;
    }

    /**
     * Get destination subdirectory.
     *
     * @return string
     */
    public function getDestinationSubdir()
    {
        return $this->destinationSubdir;
    }

    /**
     * Check if image is cached.
     *
     * @return bool
     */
    public function isCached()
    {
        if (is_string($this->newFile)) {
            return $this->fileExists($this->newFile);
        }
        return false;
    }

    /**
     * Set watermark file name
     *
     * @param string $file Watermark file path
     *
     * @return $this
     */
    public function setWatermarkFile($file)
    {
        $this->watermarkFile = $file;
        return $this;
    }

    /**
     * Get watermark file name
     *
     * @return string
     */
    public function getWatermarkFile()
    {
        return $this->watermarkFile;
    }

    /**
     * Get relative watermark file path or false if file not found.
     *
     * @return string | bool
     */
    protected function getWatermarkFilePath()
    {
        $filePath = false;

        if (!($file = $this->getWatermarkFile())) {
            return $filePath;
        }
        $baseDir = $this->uploader->getBasePath();

        $storeId = $this->storeManager->getStore()->getId();
        $websiteId = $this->storeManager->getWebsite()->getId();
        $candidates = [
            $baseDir . '/watermark/stores/' . $storeId . $file,
            $baseDir . '/watermark/websites/' . $websiteId . $file,
            $baseDir . '/watermark/default/' . $file,
            $baseDir . '/watermark/' . $file,
        ];
        foreach ($candidates as $candidate) {
            if ($this->mediaDirectory->isExist($candidate)) {
                $filePath = $this->mediaDirectory->getAbsolutePath($candidate);
                break;
            }
        }
        if (!$filePath) {
            $filePath = $this->viewFileSystem->getStaticFileName($file);
        }

        return $filePath;
    }

    /**
     * Set watermark position
     *
     * @param string $position Watermark position code
     *
     * @return $this
     */
    public function setWatermarkPosition($position)
    {
        $this->watermarkPosition = $position;
        return $this;
    }

    /**
     * Get watermark position
     *
     * @return string
     */
    public function getWatermarkPosition()
    {
        return $this->watermarkPosition;
    }

    /**
     * Set watermark image opacity
     *
     * @param int $imageOpacity Opacity value (0-100)
     *
     * @return $this
     */
    public function setWatermarkImageOpacity($imageOpacity)
    {
        $this->watermarkImageOpacity = $imageOpacity;
        return $this;
    }

    /**
     * Get watermark image opacity
     *
     * @return int
     */
    public function getWatermarkImageOpacity()
    {
        return $this->watermarkImageOpacity;
    }

    /**
     * Set watermark size
     *
     * @param array $size Size array ['width' => int, 'height' => int]
     *
     * @return $this
     */
    public function setWatermarkSize($size)
    {
        if (is_array($size)) {
            $this->setWatermarkWidth($size['width'])
                ->setWatermarkHeight($size['height']);
        }
        return $this;
    }

    /**
     * Set watermark width
     *
     * @param int $width Watermark width in pixels
     *
     * @return $this
     */
    public function setWatermarkWidth($width)
    {
        $this->watermarkWidth = $width;
        return $this;
    }

    /**
     * Get watermark width
     *
     * @return int
     */
    public function getWatermarkWidth()
    {
        return $this->watermarkWidth;
    }

    /**
     * Set watermark height
     *
     * @param int $height Watermark height in pixels
     *
     * @return $this
     */
    public function setWatermarkHeight($height)
    {
        $this->watermarkHeight = $height;
        return $this;
    }

    /**
     * Get watermark height
     *
     * @return string
     */
    public function getWatermarkHeight()
    {
        return $this->watermarkHeight;
    }

    /**
     * Clear image cache.
     *
     * @return void
     */
    public function clearCache()
    {
        $directory = $this->uploader->getBasePath() . '/cache';
        $this->mediaDirectory->delete($directory);

        $this->coreFileStorageDatabase->deleteFolder(
            $this->mediaDirectory->getAbsolutePath($directory)
        );
    }

    /**
     * Check if file exists on filesystem or in database storage.
     *
     * @param string $filename File path to check
     *
     * @return bool
     */
    protected function fileExists($filename)
    {
        if ($this->mediaDirectory->isFile($filename)) {
            return true;
        } else {
            return $this->coreFileStorageDatabase->saveFileToFilesystem(
                $this->mediaDirectory->getAbsolutePath($filename)
            );
        }
    }

    /**
     * Return resized image information.
     *
     * @return array|null
     */
    public function getResizedImageInfo()
    {
        $fileInfo = null;
        if ($this->newFile === true) {
            $placeholderPath = "Sample_News::images/" . $this->entityCode
                . "/placeholder/{$this->getDestinationSubdir()}.jpg";
            $asset = $this->assetRepo->createAsset($placeholderPath);
            $img = $asset->getSourceFile();
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $fileInfo = getimagesize($img);
        } else {
            $newFilePath = $this->mediaDirectory->getAbsolutePath(
                $this->newFile
            );
            if ($this->mediaDirectory->isFile($newFilePath)) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $fileInfo = getimagesize($newFilePath);
            }
        }
        return $fileInfo;
    }
}
