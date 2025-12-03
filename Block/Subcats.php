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
 * http://mage.jscriptz.com/LICENSE
 *
 ********************************************************************
 *
 * @category   Jscriptz
 * @package    Jscriptz_Subcats
 * @author     Jason Lotzer (jasonlotzer@gmail.com)
 * @copyright  Copyright (c) 2019 Jscriptz LLC. (https://mage.jscriptz.com)
 * @license    https://mage.jscriptz.com/LICENSE.txt
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

class Subcats extends \Magento\Catalog\Block\Category\View
{
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


        parent::__construct($context, $layerResolver, $registry, $categoryHelper, $data);
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

        // 2) Native category image (if present)
        if (!$imageUrl && method_exists($child, 'getImageUrl')) {
            $imageUrl = $child->getImageUrl();
        }

        // 3) Fallback: pick a good product image from the child category,
        //     mimicking the original module behavior.
        if (!$imageUrl && $this->configHelper->isProductImageFallbackEnabled()) {
            $limit   = 10;
            $storeId = (int)$this->_storeManager->getStore()->getId();

            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
            if ((bool)$child->getIsAnchor()) {
                // Anchor categories often have products only in their children.
                // Pull from this category + all descendants.
                $categoryIds = $child->getAllChildren(true);
                if (!is_array($categoryIds)) {
                    $categoryIds = explode(',', (string)$child->getAllChildren());
                }
                $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
                if (empty($categoryIds)) {
                    $categoryIds = [(int)$child->getId()];
                }

                $productCollection = $this->_productCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->addStoreFilter($storeId)
                    ->setPageSize($limit)
                    ->setCurPage(1);

                // Magento supports addCategoriesFilter on product collections; keep this defensive.
                if (method_exists($productCollection, 'addCategoriesFilter')) {
                    $productCollection->addCategoriesFilter(['in' => $categoryIds]);
                } else {
                    $productCollection->addCategoryFilter($child);
                }

                // Stable fallback ordering (we only need "a good" image)
                $productCollection->addAttributeToSort('entity_id', 'DESC');
            } else {
                // Non-anchor: products are usually assigned directly to the category
                $productCollection = $child->getProductCollection()
                    ->addAttributeToSelect('*')
                    ->setOrder('position', 'ASC')
                    ->setPageSize($limit);
            }

            foreach ($productCollection as $product) {
                // Use the same image role as the original: category_page_grid
                $image = $this->catalogImageHelper
                    ->init($product, 'category_page_grid')
                    ->constrainOnly(false)
                    ->keepAspectRatio(true)
                    ->keepFrame(true);

                if ($width && $height) {
                    $image->resize($width, $height);
                }

                $candidate = $image->getUrl();

                // Skip placeholders, just like the original
                if ($candidate && stripos($candidate, 'placeholder') === false) {
                    $imageUrl = $candidate;
                    break;
                }
            }
        }

        return $imageUrl;
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
    protected function getCategoriesByIds(array $ids)
    {
        $categories = [];

        if (!$ids) {
            return $categories;
        }

        $storeId = (int)$this->_storeManager->getStore()->getId();

        foreach ($ids as $id) {
            try {
                $category = $this->_categoryRepository->get($id, $storeId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                continue;
            }

            if (!$category->getIsActive()) {
                continue;
            }

            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * Prepared list of active child categories for the current category
     * or for an explicit list of category IDs (Page Builder).
     *
     * @return \Magento\Catalog\Model\Category[]|\Magento\Catalog\Model\ResourceModel\Category\Collection|array
     */
    /**
     * Get selected child category IDs for the current category from the 'subcats_children' attribute.
     * Empty array means: show all active children.
     *
     * @return int[]
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

    public function getChildCategories()
    {
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

        // CASE 1: explicit selection -> use that exact order
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



}
