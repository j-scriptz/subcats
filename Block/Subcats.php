<?php
declare(strict_types=1);

/**
 * Jscriptz LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://www.jscriptz.net/LICENSE
 *
 ********************************************************************
 *
 * PHP version 7
 *
 * @category  Jscriptz
 * @package   Jscriptz_Subcats
 * @author    Jason Lotzer <jasonlotzer@gmail.com>
 * @copyright 2019 Jscriptz LLC.
 * @license   https://www.jscriptz.net/LICENSE Proprietary
 * @link      https://www.jscriptz.net
 */

namespace Jscriptz\Subcats\Block;

use Magento\Framework\View\Element\Template\Context;
use Jscriptz\Subcats\Helper\Data as ConfigHelper;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
    as CategoryCollectionFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection
    as CategoryCollection;
use Jscriptz\Subcats\Model\LicenseValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Widget\Block\BlockInterface;

/**
 * Block Subcats
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class Subcats extends \Magento\Framework\View\Element\Template implements BlockInterface
{
    /**
     * Track product image paths we've already used as fallbacks,
     * so we can avoid duplicates across the Subcats grid.
     *
     * @var array<string,bool>
     */
    private $_usedFallbackImages = [];

    /**
     * Default template for widget usage (can still be overridden by layout)
     *
     * @var string
     */
    // @codingStandardsIgnoreLine
    protected $_template = 'Jscriptz_Subcats::subcats.phtml';

    /**
     * Layer resolver instance.
     *
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    protected $layerResolver;

    /**
     * Category helper instance.
     *
     * @var \Magento\Catalog\Helper\Category
     */
    protected $categoryHelper;

    /**
     * Filesystem instance.
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * Image adapter factory instance.
     *
     * @var \Magento\Framework\Image\AdapterFactory
     */
    protected $imageFactory;

    /**
     * Category repository instance.
     *
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * Registry instance.
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Category instance.
     *
     * @var \Magento\Catalog\Model\Category
     */
    protected $category;

    /**
     * Product collection factory instance.
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * Category collection factory instance.
     *
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * Category factory instance.
     *
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * Config helper instance.
     *
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * Catalog image helper instance.
     *
     * @var CatalogImageHelper
     */
    protected $catalogImageHelper;

    /**
     * License validator instance.
     *
     * @var LicenseValidator
     */
    protected $licenseValidator;

    /**
     * Constructor.
     *
     * @param Context                                                         $context                   Context instance
     * @param \Magento\Catalog\Model\Layer\Resolver                           $layerResolver             Layer resolver
     * @param \Magento\Framework\Registry                                     $registry                  Registry instance
     * @param \Magento\Catalog\Helper\Category                                $categoryHelper            Category helper
     * @param \Magento\Framework\Filesystem                                   $filesystem                Filesystem
     * @param \Magento\Framework\Image\AdapterFactory                         $imageFactory              Image factory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  $productCollectionFactory  Product collection
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory Category collection
     * @param \Magento\Catalog\Model\CategoryFactory                          $categoryFactory           Category factory
     * @param \Magento\Catalog\Model\CategoryRepository                       $categoryRepository        Category repository
     * @param ConfigHelper                                                    $configHelper              Config helper instance
     * @param CatalogImageHelper                                              $catalogImageHelper        Catalog image helper
     * @param LicenseValidator                                                $licenseValidator          License validator
     * @param array                                                           $data                      Block data array
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        ConfigHelper $configHelper,
        CatalogImageHelper $catalogImageHelper,
        LicenseValidator $licenseValidator,
        array $data = []
    ) {
        $this->layerResolver = $layerResolver;
        $this->registry = $registry;
        $this->categoryHelper = $categoryHelper;
        $this->filesystem = $filesystem;
        $this->imageFactory = $imageFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->configHelper = $configHelper;
        $this->catalogImageHelper = $catalogImageHelper;
        $this->licenseValidator = $licenseValidator;

        parent::__construct($context, $data);
    }

    /**
     * Get subcategory image url.
     *
     * @param \Magento\Catalog\Model\Category $child Category instance
     *
     * @return string|null
     */
    public function getSubcategoryImageUrl(\Magento\Catalog\Model\Category $child)
    {
        // Prefer your configured subcat dimensions; fall back to whatever
        // the parent block might provide (if anything).
        $width  = (int)$this->configHelper->getSubcatImageWidth();
        $height = (int)$this->configHelper->getSubcatImageHeight();

        if (!$width && method_exists($this, 'getImageWidth')) {
            $width = (int)$this->getImageWidth();
        }
        if (!$height && method_exists($this, 'getImageHeight')) {
            $height = (int)$this->getImageHeight();
        }

        // 1) Explicit image via Jscriptz helper (subcat_image / additional_image)
        $imageUrl = $this->configHelper->getImageUrl($child);
        if ($imageUrl) {
            return $imageUrl;
        }

        // 2) Native category image (if present)
        if (method_exists($child, 'getImageUrl')) {
            $imageUrl = $child->getImageUrl();
            if ($imageUrl) {
                return $imageUrl;
            }
        } else {
            $imageFile = (string)$child->getData('image');
            if ($imageFile !== '') {
                $mediaBase = $this->_storeManager->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                );
                $imageUrl = $mediaBase . 'catalog/category/'
                    . ltrim($imageFile, '/');
                if ($imageUrl) {
                    return $imageUrl;
                }
            }
        }

        // 3) Product image fallback (Men / Women / Performance Sportswear New, etc.)
        //    Only runs if enabled in config.
        if (!$this->configHelper->isProductImageFallbackEnabled()) {
            return $imageUrl;
        }

        // Use ALL descendants (including category) as candidates.
        // getAllChildren(true) is array of IDs; getAllChildren() is comma string.
        $categoryIds = [];
        $allChildren = $child->getAllChildren(true);

        if (is_array($allChildren)) {
            $categoryIds = $allChildren;
        } else {
            $categoryIds = array_filter(
                array_map(
                    'intval',
                    explode(',', (string)$child->getAllChildren())
                )
            );
        }

        if (empty($categoryIds)) {
            $categoryIds = [(int)$child->getId()];
        }

        $categoryIds = array_values(
            array_unique(array_map('intval', $categoryIds))
        );

        $storeId = (int)$this->_storeManager->getStore()->getId();

        /**
         * Product collection for fallback images
         *
         * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
         */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['small_image'])
            ->addStoreFilter($storeId)
            ->addAttributeToFilter('small_image', ['neq' => 'no_selection'])
            ->addCategoriesFilter(['in' => $categoryIds])
            ->setPageSize(30)
            ->setCurPage(1);

        // Track used fallback images across this block
        // (static, so each card gets a unique one if possible)
        static $usedFallbackImages = [];

        foreach ($collection as $product) {
            $image = $this->catalogImageHelper
                ->init($product, 'category_page_grid')
                ->constrainOnly(false)
                ->keepAspectRatio(true)
                ->keepFrame(true);

            if ($width && $height) {
                $image->resize($width, $height);
            }

            $candidate = $image->getUrl();

            // Skip placeholders
            if (!$candidate || stripos($candidate, 'placeholder') !== false) {
                continue;
            }

            // Enforce uniqueness (path-only key so CDN params don't matter)
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $imageKey = parse_url($candidate, PHP_URL_PATH) ?: $candidate;
            if (isset($usedFallbackImages[$imageKey])) {
                continue;
            }

            $usedFallbackImages[$imageKey] = true;
            return $candidate;
        }

        // No usable fallback found
        return null;
    }

    /**
     * Get a unique fallback product image for a category.
     *
     * If category has no products, also consider child categories.
     * Ensures each card gets different fallback image by tracking used paths.
     *
     * @param \Magento\Catalog\Model\Category $category Category instance
     *
     * @return string|null
     */
    protected function getUniqueFallbackProductImage(
        \Magento\Catalog\Model\Category $category
    ): ?string {
        // Start with this category
        $categoryIds = [(int)$category->getId()];

        // If no products, include children as candidates for fallback
        $productCount = (int)$category->getProductCount();
        if ($productCount === 0) {
            $childrenIds = array_filter(
                array_map(
                    'intval',
                    explode(',', (string)$category->getChildren())
                )
            );

            if (!empty($childrenIds)) {
                $categoryIds = array_values(
                    array_unique(
                        array_merge(
                            $categoryIds,
                            $childrenIds
                        )
                    )
                );
            }
        }

        /**
         * Product collection for unique fallback images
         *
         * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
         */
        $collection = $this->productCollectionFactory->create();

        $collection->addAttributeToSelect(['small_image'])
            ->addAttributeToFilter('small_image', ['neq' => 'no_selection'])
            ->addCategoriesFilter(['in' => $categoryIds])
            ->setPageSize(30)
            ->setCurPage(1);

        // 1) Try to find a product image we haven't used yet
        foreach ($collection as $product) {
            $imageUrl = $this->catalogImageHelper
                ->init($product, 'category_page_grid')
                ->keepAspectRatio(true)
                ->getUrl();

            // Use path as stable key so CDN query strings don't break
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $imageKey = parse_url($imageUrl, PHP_URL_PATH) ?: $imageUrl;

            if (!isset($this->_usedFallbackImages[$imageKey])) {
                $this->_usedFallbackImages[$imageKey] = true;
                return $imageUrl;
            }
        }

        // 2) As last resort, allow duplicate rather than blank card
        $product = $collection->getFirstItem();
        if ($product && $product->getId()) {
            $imageUrl = $this->catalogImageHelper
                ->init($product, 'category_page_grid')
                ->keepAspectRatio(true)
                ->getUrl();

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $imageKey = parse_url($imageUrl, PHP_URL_PATH) ?: $imageUrl;
            $this->_usedFallbackImages[$imageKey] = true;

            return $imageUrl;
        }

        return null;
    }

    /**
     * Can show subcategories
     *
     * @return bool
     */
    public function canShowSubcategories(): bool
    {
        // 1) Global on/off switch
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        $storeId = (int)$this->_storeManager->getStore()->getId();

        // 2) License gate: either VALID LICENSE or ACTIVE TRIAL
        if (!$this->_isLicenseOrTrialActive($storeId)) {
            return false;
        }

        // 3) Now check the actual category context

        // Page Builder explicit selection
        $selectedIds = $this->getSelectedCategoryIds();
        if (!empty($selectedIds)) {
            $children = $this->getChildCategories();
            return (bool)$children && count($children);
        }

        // Fallback: children of current category
        $category = $this->getCurrentCategory();
        if (!$category || !$category->getIsActive()) {
            return false;
        }

        $children = $this->getChildCategories();
        return (bool)$children && count($children);
    }

    /**
     * Returns true if there is a valid license OR the free trial is active.
     *
     * @param int $storeId Store ID
     *
     * @return bool
     */
    private function _isLicenseOrTrialActive(int $storeId): bool
    {
        // 1) Full license: let LicenseValidator decide
        if ($this->licenseValidator && $this->licenseValidator->isValid($storeId)) {
            return true;
        }

        // 2) Otherwise, fall back to trial logic
        return $this->_isTrialActive();
    }

    /**
     * Free trial check, based on canonical status from the Jscriptz_License server.
     *
     * @return bool
     */
    private function _isTrialActive(): bool
    {
        // Trial expired flag set by the License server via the Update API.
        $expiredFlag = (string)$this->_scopeConfig->getValue(
            'jscriptz_subcats/license/trial_expired',
            'store'
        );

        if ($expiredFlag === '1') {
            return false;
        }

        // Fallback: if for some reason the flag is missing, but the License Status
        // clearly indicates an expired trial, also treat it as expired.
        $status = (string)$this->_scopeConfig->getValue(
            \Jscriptz\Subcats\Model\LicenseValidator::XML_PATH_LICENSE_STATUS,
            'store'
        );
        $lower = strtolower($status);
        if ($expiredFlag === '' && strpos($lower, 'free trial has expired') !== false) {
            return false;
        }

        // Otherwise, be permissive: either an active trial or a temporary
        // inability to reach the license server should not hard-block the UI.
        return true;
    }

    /**
     * Resize category image.
     *
     * @param mixed $image  Image filename
     * @param mixed $width  Desired width
     * @param mixed $height Desired height
     *
     * @return string|bool
     */
    public function resize($image, $width = null, $height = null)
    {
        $mediaDirectory = $this->filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        );
        $absolutePath = $mediaDirectory->getAbsolutePath(
            'catalog/category/'
        ) . $image;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (!file_exists($absolutePath)) {
            return false;
        }
        $imageResized = $mediaDirectory->getAbsolutePath(
            'resized/' . $width . '/'
        ) . $image;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (!file_exists($imageResized)) {
            // Create image factory
            $imageResize = $this->imageFactory->create();
            $imageResize->open($absolutePath);
            $imageResize->constrainOnly(true);
            $imageResize->keepTransparency(true);
            $imageResize->keepFrame(false);
            $imageResize->keepAspectRatio(true);
            $imageResize->resize($width, $height);
            // Destination folder
            $destination = $imageResized;
            // Save image
            $imageResize->save($destination);
        }
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
        $resizedURL = $baseUrl . 'resized/' . $width . '/' . $image;
        return $resizedURL;
    }

    /**
     * Get category object using category factory.
     *
     * @param int $categoryId Category ID
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategory($categoryId)
    {
        $this->category = $this->categoryFactory->create();
        $this->category->load($categoryId);
        return $this->category;
    }

    /**
     * Get category object using category repository.
     *
     * @param int $categoryId Category ID
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategoryById($categoryId)
    {
        return $this->categoryRepository->get($categoryId);
    }

    /**
     * Get all children categories IDs.
     *
     * @param boolean  $asArray    Return as array or string
     * @param int|bool $categoryId Category ID or false
     *
     * @return array|string
     */
    public function getAllChildren($asArray = false, $categoryId = false)
    {
        if ($this->category) {
            return $this->category->getAllChildren($asArray);
        } else {
            return $this->getCategory($categoryId)->getAllChildren($asArray);
        }
    }

    /**
     * Retrieve children ids comma separated.
     *
     * @param int|bool $categoryId Category ID or false
     *
     * @return string
     */
    public function getChildren($categoryId = false)
    {
        if ($this->category) {
            return $this->category->getChildren();
        } else {
            return $this->getCategory($categoryId)->getChildren();
        }
    }

    /**
     * Retrieve current store categories
     *
     * @param bool|string $sorted       Sort order
     * @param bool        $asCollection Return as collection
     * @param bool        $toLoad       Load categories
     *
     * @return \Magento\Framework\Data\Tree\Node\Collection|
     *         \Magento\Catalog\Model\ResourceModel\Category\Collection|array
     */
    public function getStoreCategories(
        $sorted = false,
        $asCollection = false,
        $toLoad = true
    ) {
        return $this->categoryHelper->getStoreCategories();
    }

    /**
     * Get parent category object.
     *
     * @param int|bool $categoryId Category ID or false
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getParentCategory($categoryId = false)
    {
        if ($this->category) {
            return $this->category->getParentCategory();
        } else {
            return $this->getCategory($categoryId)->getParentCategory();
        }
    }

    /**
     * Get parent category identifier.
     *
     * @param int|bool $categoryId Category ID or false
     *
     * @return int
     */
    public function getParentId($categoryId = false)
    {
        if ($this->category) {
            return $this->category->getParentId();
        } else {
            return $this->getCategory($categoryId)->getParentId();
        }
    }

    /**
     * Get all parent categories ids.
     *
     * @param int|bool $categoryId Category ID or false
     *
     * @return array
     */
    public function getParentIds($categoryId = false)
    {
        if ($this->category) {
            return $this->category->getParentIds();
        } else {
            return $this->getCategory($categoryId)->getParentIds();
        }
    }

    /**
     * Normalize selected category IDs from block data (e.g. Page Builder).
     *
     * @return int[]
     */
    public function getSelectedCategoryIds()
    {
        $ids = $this->getData('category_ids');
        if (!$ids) {
            return [];
        }

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter(
            $ids,
            function ($id) {
                return $id > 0;
            }
        );

        return array_values(array_unique($ids));
    }

    /**
     * Load categories by explicit IDs (used by Page Builder / widgets).
     *
     * @param int[] $ids Category IDs
     *
     * @return \Magento\Catalog\Model\Category[]
     */
    public function getCategoriesByIds(array $ids)
    {
        // Normalize and keep explicit order from widget/category config
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (empty($ids)) {
            return [];
        }

        /**
         * Category collection
         *
         * @var CategoryCollection $collection
         */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['in' => $ids])
            ->addIsActiveFilter();

        // Preserve order of $ids (same trick as selected_subcats)
        $fieldExpr = 'FIELD(e.entity_id,' . implode(',', $ids) . ')';
        $collection->getSelect()->order($fieldExpr);

        return $collection;
    }

    /**
     * Get the category context for rendering Subcats.
     *
     * Priority: 1) category_ids passed to block/widget (first ID),
     * 2) current_category set on block, 3) current_category from registry
     * (normal category pages).
     *
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getCurrentCategory()
    {
        // 1) Explicit override via category_ids (widget/CMS/Page Builder)
        $selectedIds = $this->getSelectedCategoryIds();
        if (!empty($selectedIds)) {
            $storeId = (int)$this->_storeManager->getStore()->getId();
            $categoryId = (int)reset($selectedIds);

            try {
                $category = $this->categoryRepository->get(
                    $categoryId,
                    $storeId
                );
                if ($category && $category->getId()
                    && $category->getIsActive()
                ) {
                    return $category;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                unset($e); // Intentionally empty - fallback behavior
            }
        }

        // 2) Block data / registry (normal category page flow)
        $category = $this->getData('current_category')
            ?: $this->registry->registry('current_category');

        if ($category instanceof \Magento\Catalog\Model\Category) {
            return $category;
        }

        return null;
    }

    /**
     * List of active child categories for current category.
     *
     * Used for explicit list of category IDs (Page Builder).
     *
     * @return \Magento\Catalog\Model\Category[]|
     *         \Magento\Catalog\Model\ResourceModel\Category\Collection|array
     */
    public function getCurrentCategoryFilterIds()
    {
        $category = $this->getCurrentCategory();
        if (!$category) {
            return [];
        }

        $raw = $category->getData('subcats_children');
        if (!$raw) {
            return [];
        }

        if (is_array($raw)) {
            $values = $raw;
        } else {
            $values = explode(',', (string)$raw);
        }

        $ids = [];
        foreach ($values as $value) {
            $value = (int)trim((string)$value);
            if ($value > 0) {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * List of active child categories for effective category.
     *
     * Either current category or explicit category_ids override.
     *
     * @return \Magento\Catalog\Model\Category[]|
     *         \Magento\Catalog\Model\ResourceModel\Category\Collection|array
     */
    public function getChildCategories()
    {
        // CASE 0: explicit IDs passed via widget/CMS/Page Builder
        // Show exactly selected categories, NOT their children
        $explicitIds = $this->getSelectedCategoryIds();
        if (!empty($explicitIds)) {
            return $this->getCategoriesByIds($explicitIds);
        }

        /**
         * Current category
         *
         * @var Category|null $category
         */
        $category = $this->getData('current_category')
            ?: $this->registry->registry('current_category');

        if (!$category || !$category->getId()) {
            return [];
        }

        // Value from multiselect (comma-separated IDs, order from admin)
        $selectedRaw = (string)$category->getData('subcats_children');
        $orderedIds = [];

        if ($selectedRaw !== '') {
            foreach (explode(',', $selectedRaw) as $id) {
                $id = (int)trim($id);
                if ($id > 0) {
                    $orderedIds[] = $id;
                }
            }
        }

        // CASE 1: explicit selection (Subcategories to display)
        if (!empty($orderedIds)) {
            /**
             * Category collection
             *
             * @var CategoryCollection $collection
             */
            $collection = $this->categoryCollectionFactory->create();

            $collection->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', ['in' => $orderedIds])
                ->addIsActiveFilter();

            // Force MySQL to return rows in same order as $orderedIds
            $fieldExpr = 'FIELD(e.entity_id,'
                . implode(',', $orderedIds) . ')';
            $collection->getSelect()->order($fieldExpr);

            return $collection;
        }

        // CASE 2: no explicit selection, show next-level children
        $children = $category->getChildrenCategories();
        $children->addAttributeToSelect('*')
            ->addIsActiveFilter()
            ->addAttributeToSort('position', 'ASC');

        return $children;
    }

    /**
     * Effective design preset for this block instance.
     *
     * @return string
     */
    public function getDesignPreset()
    {
        $override = (string)$this->getData('design_preset');
        if ($override !== '') {
            return $override;
        }

        return (string)$this->configHelper->getDesignPreset();
    }

    /**
     * Whether the grow-on-hover effect is enabled for subcategory cards.
     *
     * @return bool
     */
    public function isGrowEnabled()
    {
        return (bool) $this->configHelper->getGrowEnabled();
    }

    /**
     * CSS class for the container based on preset.
     *
     * @return string
     */
    public function getDesignPresetCssClass()
    {
        $preset = preg_replace('/[^a-z0-9_-]/i', '', $this->getDesignPreset());
        if ($preset === '') {
            $preset = 'default';
        }

        return 'jscriptz-subcats--preset-' . $preset;
    }

    /**
     * Return a plain-text description for the subcategory.
     *
     * @param \Magento\Catalog\Model\Category $child Category instance
     *
     * @return string
     */
    public function getSubcategoryDescription(
        \Magento\Catalog\Model\Category $child
    ) {
        $description = (string)$child->getDescription();
        if ($description === '') {
            return '';
        }

        return strip_tags($description);
    }

    /**
     * Widget-specific column span (12 / 6 / 4 / 3 / 2) for desktop.
     *
     * Returns null when not set so global/category settings are used.
     *
     * @return int|null
     */
    public function getWidgetDesktopSpan(): ?int
    {
        $value = $this->getData('columns_desktop');
        if ($value === null || $value === '' || (int)$value <= 0) {
            return null;
        }
        return (int)$value;
    }

    /**
     * Get widget tablet span.
     *
     * @return ?int
     */
    public function getWidgetTabletSpan(): ?int
    {
        $value = $this->getData('columns_tablet');
        if ($value === null || $value === '' || (int)$value <= 0) {
            return null;
        }
        return (int)$value;
    }

    /**
     * Get widget phone span.
     *
     * @return ?int
     */
    public function getWidgetPhoneSpan(): ?int
    {
        $value = $this->getData('columns_mobile');
        if ($value === null || $value === '' || (int)$value <= 0) {
            return null;
        }
        return (int)$value;
    }

    /**
     * Build inline CSS custom properties that override column widths.
     *
     * Used for this particular Subcats block (useful for widgets).
     * It reuses the same 12-column grid math as the global design block:
     *   span = 4  =>  3 columns  =>  width calc based on gaps.
     *
     * @return string
     */
    public function getWidgetCssOverrides(): string
    {
        $vars    = [];
        $spacing = 'var(--js-subcats-card-spacing, 20px)';

        $desktopSpan = $this->getWidgetDesktopSpan();
        if ($desktopSpan) {
            if ($desktopSpan === 5) {
                $desktopCols = 5;
                $desktopGaps = 4;

                $vars['--js-subcats-col-width-desktop'] = sprintf(
                    'calc((100%% - (%s * %d)) / %d)',
                    $spacing,
                    $desktopGaps,
                    $desktopCols
                );
            } elseif (12 % $desktopSpan === 0) {
                $desktopCols = (int) (12 / $desktopSpan);
                $desktopGaps = max(0, $desktopCols - 1);

                $vars['--js-subcats-col-width-desktop'] = sprintf(
                    'calc((100%% - (%s * %d)) / %d)',
                    $spacing,
                    $desktopGaps,
                    $desktopCols
                );
            } else {
                $vars['--js-subcats-col-width-desktop'] = sprintf(
                    '%.6f%%',
                    100 * ($desktopSpan / 12)
                );
            }
        }

        $tabletSpan = $this->getWidgetTabletSpan();
        if ($tabletSpan) {
            if ($tabletSpan === 5) {
                $tabletCols = 5;
                $tabletGaps = 4;

                $vars['--js-subcats-col-width-tablet'] = sprintf(
                    'calc((100%% - (%s * %d)) / %d)',
                    $spacing,
                    $tabletGaps,
                    $tabletCols
                );
            } elseif (12 % $tabletSpan === 0) {
                $tabletCols = (int) (12 / $tabletSpan);
                $tabletGaps = max(0, $tabletCols - 1);

                $vars['--js-subcats-col-width-tablet'] = sprintf(
                    'calc((100%% - (%s * %d)) / %d)',
                    $spacing,
                    $tabletGaps,
                    $tabletCols
                );
            } else {
                $vars['--js-subcats-col-width-tablet'] = sprintf(
                    '%.6f%%',
                    100 * ($tabletSpan / 12)
                );
            }
        }

        $phoneSpan = $this->getWidgetPhoneSpan();
        if ($phoneSpan) {
            if ($phoneSpan === 5) {
                $phoneCols = 5;
                $phoneGaps = 4;

                $vars['--js-subcats-col-width-phone'] = sprintf(
                    'calc((100%% - (%s * %d)) / %d)',
                    $spacing,
                    $phoneGaps,
                    $phoneCols
                );
            } elseif (12 % $phoneSpan === 0) {
                $phoneCols = (int) (12 / $phoneSpan);
                $phoneGaps = max(0, $phoneCols - 1);

                $vars['--js-subcats-col-width-phone'] = sprintf(
                    'calc((100%% - (%s * %d)) / %d)',
                    $spacing,
                    $phoneGaps,
                    $phoneCols
                );
            } else {
                $vars['--js-subcats-col-width-phone'] = sprintf(
                    '%.6f%%',
                    100 * ($phoneSpan / 12)
                );
            }
        }

        if (!$vars) {
            return '';
        }

        $pairs = [];
        foreach ($vars as $name => $value) {
            $pairs[] = $name . ':' . $value;
        }

        return implode(';', $pairs);
    }
}
