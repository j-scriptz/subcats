<?php
declare(strict_types=1);

namespace Jscriptz\Subcats\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Option\ArrayInterface;

/**
 * Category multiselect source model for the Jscriptz_Subcats widget.
 *
 * Produces a flattened category tree with FULL PATH labels, e.g.:
 *  "Women / Tops", "Men / Tops", etc.
 */
class CategoryMultiselect implements ArrayInterface
{
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Return options array for multiselect.
     *
     * @param int|null $forStoreId Optional store ID to filter categories by.
     * @return array[]
     */
    public function toOptionArray($forStoreId = null): array
    {
        $options = [];

        if ($forStoreId !== null && $forStoreId > 0) {
            $store = $this->storeManager->getStore($forStoreId);
        } else {
            $store = $this->storeManager->getStore();
        }
        $storeId = (int) $store->getId();
        $rootId  = (int) $store->getRootCategoryId();

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', 1)
            ->setStoreId($storeId)
            // Only categories under this store's root
            ->addFieldToFilter('path', ['like' => '1/' . $rootId . '/%'])
            // Sort by path so parents come before children
            ->addAttributeToSort('path', 'ASC');

        // Build an ID => category map and a list to iterate later
        $categoriesById = [];
        $categoriesList = [];

        foreach ($collection as $category) {
            $id = (int) $category->getId();
            $categoriesById[$id] = $category;
            $categoriesList[]    = $category;
        }

        foreach ($categoriesList as $category) {
            $id = (int) $category->getId();

            // Skip the store root itself (we only care about children)
            if ($id === $rootId) {
                continue;
            }

            $path = (string) $category->getPath();
            if ($path === '') {
                continue;
            }

            $pathIds = array_map('intval', explode('/', $path));

            // Find where the store root sits in the path, then take everything after it
            $rootIndex = array_search($rootId, $pathIds, true);
            if ($rootIndex === false) {
                continue;
            }

            $tailIds = array_slice($pathIds, $rootIndex + 1); // children of the root

            $pathParts = [];
            foreach ($tailIds as $tailId) {
                if (isset($categoriesById[$tailId])) {
                    $pathParts[] = (string) $categoriesById[$tailId]->getName();
                }
            }

            // Fallback: if we couldn't build a path for some reason, just use the category name
            if (empty($pathParts)) {
                $label = (string) $category->getName();
            } else {
                // e.g. "Women / Tops", "Men / Tops"
                $label = implode(' / ', $pathParts);
            }

            $options[] = [
                'value' => $id,
                'label' => $label,
            ];
        }

        return $options;
    }
}
