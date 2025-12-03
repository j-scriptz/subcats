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


namespace Jscriptz\Subcats\Model\Config\Source;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

class CategoryChildren implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    /**
     * Return options for the current category's subtree, with indentation.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $categoryId = (int)$this->request->getParam('id');
        if (!$categoryId) {
            // No current category context; nothing to select
            return [];
        }

        $storeId = (int)$this->storeManager->getStore()->getId();

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', 1)
            ->setStoreId($storeId);

        /** @var Category $current */
        $current = $collection->getItemById($categoryId);
        if (!$current) {
            // load current explicitly
            $current = $collection->getNewEmptyItem()->load($categoryId);
        }

        if (!$current || !$current->getId()) {
            return [];
        }

        // Get descendants of current category, ordered by path
        $path = $current->getPath() . '/';
        $descendants = $this->categoryCollectionFactory->create();
        $descendants->addAttributeToSelect(['name', 'path', 'level', 'is_active'])
            ->addAttributeToFilter('path', ['like' => $path . '%'])
            ->addAttributeToFilter('is_active', 1)
            ->setStoreId($storeId)
            ->setOrder('path', 'ASC');

        $options = [];
        $baseLevel = (int)$current->getLevel();

        /** @var Category $cat */
        foreach ($descendants as $cat) {
            $levelDiff = max(0, (int)$cat->getLevel() - $baseLevel - 1);
            $prefix = str_repeat('— ', $levelDiff);
            $options[] = [
                'value' => (string)$cat->getId(),
                'label' => $prefix . $cat->getName(),
            ];
        }

        return $options;
    }
}
