<?php
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
 * @category   Jscriptz
 * @package    Jscriptz_Subcats
 * @author     Jason Lotzer (jasonlotzer@gmail.com)
 * @copyright  Copyright (c) 2019 Jscriptz LLC. (https://www.jscriptz.net/)
 * @license    https://www.jscriptz.net/LICENSE
 */

namespace Jscriptz\Subcats\Block;

use Magento\Framework\View\Element\Template\Context;
use Jscriptz\Subcats\Helper\Data as ConfigHelper;
use Magento\Catalog\Helper\Image as CatalogImageHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Jscriptz\Subcats\Model\LicenseValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Widget\Block\BlockInterface;

class Subcats extends \Magento\Framework\View\Element\Template implements BlockInterface
{
    /**
     * Track product image paths we’ve already used as fallbacks,
     * so we can avoid duplicates across the Subcats grid.
     *
     * @var array<string,bool>
     */
    private $usedFallbackImages = [];

    /**
     * Default template for widget usage (can still be overridden by layout)
     *
     * @var string
     */
    protected $_template = 'Jscriptz_Subcats::subcats.phtml';

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * @var \Magento\Framework\Image\AdapterFactory
     */
    protected $_imageFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */

    protected $_categoryRepository;
    /**
     * @var \Magento\Framework\Registry
     */

    protected $_registry;
    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $_category;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
    * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
    */
    protected $_categoryCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var CatalogImageHelper
     */
    protected $catalogImageHelper;

    /**
     * @var LicenseValidator
     */
    protected $licenseValidator;

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
        $this->_layerResolver            = $layerResolver;
        $this->_registry                 = $registry;
        $this->_categoryHelper           = $categoryHelper;
        $this->_filesystem               = $filesystem;
        $this->_imageFactory             = $imageFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_categoryFactory          = $categoryFactory;
        $this->_categoryRepository       = $categoryRepository;
        $this->configHelper              = $configHelper;
        $this->catalogImageHelper        = $catalogImageHelper;
        $this->licenseValidator        = $licenseValidator;

        parent::__construct($context, $data);
    }

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
                $mediaBase = $this->_storeManager
                    ->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $imageUrl = $mediaBase . 'catalog/category/' . ltrim($imageFile, '/');
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

        // Use ALL descendants (including the category itself) as candidates.
        // getAllChildren(true) is an array of IDs; getAllChildren() is a comma string.
        $categoryIds = [];
        $allChildren = $child->getAllChildren(true);

        if (is_array($allChildren)) {
            $categoryIds = $allChildren;
        } else {
            $categoryIds = array_filter(array_map(
                'intval',
                explode(',', (string)$child->getAllChildren())
            ));
        }

        if (empty($categoryIds)) {
            $categoryIds = [(int)$child->getId()];
        }

        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));

        $storeId = (int)$this->_storeManager->getStore()->getId();

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect(['small_image'])
            ->addStoreFilter($storeId)
            ->addAttributeToFilter('small_image', ['neq' => 'no_selection'])
            ->addCategoriesFilter(['in' => $categoryIds])
            ->setPageSize(30)
            ->setCurPage(1);

        // Track used fallback images across this block (static, so each card gets a unique one if possible)
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
     * - If the category has no products, we also consider its child categories.
     * - Ensures, as much as possible, that each card gets a different fallback
     *   image by tracking already-used image paths in $this->usedFallbackImages.
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return string|null
     */
    protected function getUniqueFallbackProductImage(
        \Magento\Catalog\Model\Category $category
    ): ?string {
        // Start with this category
        $categoryIds = [(int)$category->getId()];

        // If it has no products, include children as candidates for fallback
        $productCount = (int)$category->getProductCount();
        if ($productCount === 0) {
            $childrenIds = array_filter(array_map(
                'intval',
                explode(',', (string)$category->getChildren())
            ));

            if (!empty($childrenIds)) {
                $categoryIds = array_values(array_unique(array_merge(
                    $categoryIds,
                    $childrenIds
                )));
            }
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->_productCollectionFactory->create();

        $collection->addAttributeToSelect(['small_image'])
            ->addAttributeToFilter('small_image', ['neq' => 'no_selection'])
            ->addCategoriesFilter(['in' => $categoryIds])
            ->setPageSize(30)
            ->setCurPage(1);

        // 1) Try to find a product image we haven’t used yet
        foreach ($collection as $product) {
            $imageUrl = $this->catalogImageHelper
                ->init($product, 'category_page_grid')
                ->keepAspectRatio(true)
                ->getUrl();

            // Use the path as a stable key so CDN query strings etc. don’t break uniqueness
            $imageKey = parse_url($imageUrl, PHP_URL_PATH) ?: $imageUrl;

            if (!isset($this->usedFallbackImages[$imageKey])) {
                $this->usedFallbackImages[$imageKey] = true;
                return $imageUrl;
            }
        }

        // 2) As a last resort, allow a duplicate rather than a blank card
        $product = $collection->getFirstItem();
        if ($product && $product->getId()) {
            $imageUrl = $this->catalogImageHelper
                ->init($product, 'category_page_grid')
                ->keepAspectRatio(true)
                ->getUrl();

            $imageKey = parse_url($imageUrl, PHP_URL_PATH) ?: $imageUrl;
            $this->usedFallbackImages[$imageKey] = true;

            return $imageUrl;
        }

        return null;
    }

    public function canShowSubcategories(): bool
    {
        // 1) Global on/off switch
        if (!$this->configHelper->isEnabled()) {
            return false;
        }

        $storeId = (int)$this->_storeManager->getStore()->getId();

        // 2) License gate: either VALID LICENSE or ACTIVE TRIAL
        if (!$this->isLicenseOrTrialActive($storeId)) {
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
     */
    private function isLicenseOrTrialActive(int $storeId): bool
    {
        // 1) Full license: let LicenseValidator decide
        if ($this->licenseValidator && $this->licenseValidator->isValid($storeId)) {
            return true;
        }

        // 2) Otherwise, fall back to trial logic
        return $this->isTrialActive();
    }

    /**
     * Free trial check, based on canonical status from the Jscriptz_License server.
     */
    private function isTrialActive(): bool
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

    // pass imagename, width and height
    public function resize($image, $width = null, $height = null)
    {
        $absolutePath = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('catalog/category/').$image;
        if (!file_exists($absolutePath)) return false;
        $imageResized = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('resized/'.$width.'/').$image;
        if (!file_exists($imageResized)) { // Only resize image if not already exists.
            //create image factory...
            $imageResize = $this->_imageFactory->create();
            $imageResize->open($absolutePath);
            $imageResize->constrainOnly(TRUE);
            $imageResize->keepTransparency(TRUE);
            $imageResize->keepFrame(FALSE);
            $imageResize->keepAspectRatio(TRUE);
            $imageResize->resize($width,$height);
            //destination folder
            $destination = $imageResized ;
            //save image
            $imageResize->save($destination);
        }
        $resizedURL = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'resized/'.$width.'/'.$image;
        return $resizedURL;
  }

    /**
     * Get category object
     * Using $_categoryFactory
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategory($categoryId)
    {
        $this->_category = $this->_categoryFactory->create();
        $this->_category->load($categoryId);
        return $this->_category;
    }

    /**
     * Get category object
     * Using $_categoryRepository
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategoryById($categoryId)
    {
        return $this->_categoryRepository->get($categoryId);
    }

    /**
     * Get all children categories IDs
     *
     * @param boolean $asArray return result as array instead of comma-separated list of IDs
     * @return array|string
     */
    public function getAllChildren($asArray = false, $categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getAllChildren($asArray);
        } else {
            return $this->getCategory($categoryId)->getAllChildren($asArray);
        }
    }

    /**
     * Retrieve children ids comma separated
     *
     * @return string
     */
    public function getChildren($categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getChildren();
        } else {
            return $this->getCategory($categoryId)->getChildren();
        }
    }

    /**
     * Retrieve current store categories
     *
     * @param bool|string $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @return \Magento\Framework\Data\Tree\Node\Collection or
     * \Magento\Catalog\Model\ResourceModel\Category\Collection or array
     */
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories();
    }

    /**
     * Get parent category object
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getParentCategory($categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getParentCategory();
        } else {
            return $this->getCategory($categoryId)->getParentCategory();
        }
    }

    /**
     * Get parent category identifier
     *
     * @return int
     */
    public function getParentId($categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getParentId();
        } else {
            return $this->getCategory($categoryId)->getParentId();
        }
    }

    /**
     * Get all parent categories ids
     *
     * @return array
     */
    public function getParentIds($categoryId = false)
    {
        if ($this->_category) {
            return $this->_category->getParentIds();
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
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    /**
     * Load categories by explicit IDs (used by Page Builder / widgets).
     *
     * @param int[] $ids
     * @return \Magento\Catalog\Model\Category[]
     */
    public function getCategoriesByIds(array $ids)
    {
        // Normalize and keep the *explicit* order from the widget/category config
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (empty($ids)) {
            return [];
        }

        /** @var CategoryCollection $collection */
        $collection = $this->_categoryCollectionFactory->create();

        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['in' => $ids])
            ->addIsActiveFilter();

        // Preserve the order of $ids (same trick as for selected_subcats / subcats_children)
        $fieldExpr = 'FIELD(e.entity_id,' . implode(',', $ids) . ')';
        $collection->getSelect()->order($fieldExpr);

        return $collection;
    }


    /**
     * Get current category from block data or the registry, if available.
     *
     * This replaces Magento\Catalog\Block\Category\View::getCurrentCategory()
     * so this block can be safely used on CMS pages too.
     *
     * @return \Magento\Catalog\Model\Category|null
     */
    /**
     * Get the category context for rendering Subcats.
     *
     * Priority:
     *  1) Explicit category_ids passed to the block/widget (first ID)
     *  2) current_category set on the block
     *  3) current_category from the registry (normal category pages)
     *
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getCurrentCategory()
    {
        // 1) Explicit override via category_ids (widget / CMS / Page Builder)
        $selectedIds = $this->getSelectedCategoryIds();
        if (!empty($selectedIds)) {
            $storeId   = (int)$this->_storeManager->getStore()->getId();
            $categoryId = (int)reset($selectedIds);

            try {
                $category = $this->_categoryRepository->get($categoryId, $storeId);
                if ($category && $category->getId() && $category->getIsActive()) {
                    return $category;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // fall through to default behavior
            }
        }

        // 2) Block data / registry (normal category page flow)
        $category = $this->getData('current_category')
            ?: $this->_registry->registry('current_category');

        if ($category instanceof \Magento\Catalog\Model\Category) {
            return $category;
        }

        return null;
    }


    /**
     * Prepared list of active child categories for the current category
     * or for an explicit list of category IDs (Page Builder).
     *
     * @return \Magento\Catalog\Model\Category[]|\Magento\Catalog\Model\ResourceModel\Category\Collection|array
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
     * Prepared list of active child categories for the effective category
     * (either the current category or an explicit category_ids override).
     *
     * @return \Magento\Catalog\Model\Category[]|\Magento\Catalog\Model\ResourceModel\Category\Collection|array
     */
    public function getChildCategories()
    {
        // CASE 0: explicit IDs passed via widget / CMS / Page Builder
        // In this mode we show exactly the selected categories and do NOT
        // automatically include their children.
        $explicitIds = $this->getSelectedCategoryIds();
        if (!empty($explicitIds)) {
            return $this->getCategoriesByIds($explicitIds);
        }

        /** @var Category|null $category */
        $category = $this->getData('current_category')
            ?: $this->_registry->registry('current_category');

        if (!$category || !$category->getId()) {
            return [];
        }

        // Value from our multiselect (comma-separated IDs, in the order chosen in admin)
        $selectedRaw = (string)$category->getData('subcats_children');
        $orderedIds  = [];

        if ($selectedRaw !== '') {
            foreach (explode(',', $selectedRaw) as $id) {
                $id = (int)trim($id);
                if ($id > 0) {
                    $orderedIds[] = $id;
                }
            }
        }

        // CASE 1: explicit selection on the category (Subcategories to display)
        if (!empty($orderedIds)) {
            /** @var CategoryCollection $collection */
            $collection = $this->_categoryCollectionFactory->create();

            $collection->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', ['in' => $orderedIds])
                ->addIsActiveFilter();

            // Force MySQL to return rows in the same order as $orderedIds
            $fieldExpr = 'FIELD(e.entity_id,' . implode(',', $orderedIds) . ')';
            $collection->getSelect()->order($fieldExpr);

            return $collection;
        }

        // CASE 2: no explicit selection -> show next-level children ordered by position
        $children = $category->getChildrenCategories();
        $children->addAttributeToSelect('*')
            ->addIsActiveFilter()
            // DON'T call addUrlRewriteToResult() here – it was causing your alias error
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
     * @param \Magento\Catalog\Model\Category $child
     * @return string
     */
    public function getSubcategoryDescription(\Magento\Catalog\Model\Category $child)
    {
        $description = (string)$child->getDescription();
        if ($description === '') {
            return '';
        }

        return strip_tags($description);
    }

    /**
     * Widget-specific column span (12 / 6 / 4 / 3 / 2) for desktop.
     * Returns null when not set so global/category settings are used.
     */
    public function getWidgetDesktopSpan(): ?int
    {
        $value = $this->getData('columns_desktop');
        if ($value === null || $value === '' || (int)$value <= 0) {
            return null;
        }
        return (int)$value;
    }

    public function getWidgetTabletSpan(): ?int
    {
        $value = $this->getData('columns_tablet');
        if ($value === null || $value === '' || (int)$value <= 0) {
            return null;
        }
        return (int)$value;
    }

    public function getWidgetPhoneSpan(): ?int
    {
        $value = $this->getData('columns_mobile');
        if ($value === null || $value === '' || (int)$value <= 0) {
            return null;
        }
        return (int)$value;
    }

    /**
     * Build inline CSS custom properties that override column widths
     * for this particular Subcats block (useful for widgets).
     *
     * It reuses the same 12-column grid math as the global design block:
     *   span = 4  =>  3 columns  =>  width calc based on gaps.
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
