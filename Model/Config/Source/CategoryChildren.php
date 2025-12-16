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


namespace Jscriptz\Subcats\Model\Config\Source;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Model CategoryChildren
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class CategoryChildren implements OptionSourceInterface
{
    /**
     * Category collection factory
     *
     * @var CollectionFactory
     */
    private $_categoryCollectionFactory;

    /**
     * Request interface
     *
     * @var RequestInterface
     */
    private $_request;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Constructor.
     *
     * @param CollectionFactory     $categoryCollectionFactory Category collection factory
     * @param RequestInterface      $request                   Request interface
     * @param StoreManagerInterface $storeManager              Store manager
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ) {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_request = $request;
        $this->_storeManager = $storeManager;
    }

    /**
     * Return options for the current category's subtree, with indentation.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $categoryId = (int)$this->_request->getParam('id');
        if (!$categoryId) {
            // No current category context; nothing to select
            return [];
        }

        $storeId = (int)$this->_storeManager->getStore()->getId();

        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', 1)
            ->setStoreId($storeId);

        /**
         * Current category
         *
         * @var Category $current
         */
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
        $descendants = $this->_categoryCollectionFactory->create();
        $descendants->addAttributeToSelect(['name', 'path', 'level', 'is_active'])
            ->addAttributeToFilter('path', ['like' => $path . '%'])
            ->addAttributeToFilter('is_active', 1)
            ->setStoreId($storeId)
            ->setOrder('path', 'ASC');

        $options = [];
        $baseLevel = (int)$current->getLevel();

        /**
         * Category item
         *
         * @var Category $cat
         */
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
