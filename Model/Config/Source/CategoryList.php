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

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Model CategoryList
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class CategoryList implements OptionSourceInterface
{
    /**
     * Category collection factory.
     *
     * @var CollectionFactory
     */
    private $_categoryCollectionFactory;

    /**
     * Constructor.
     *
     * @param CollectionFactory $categoryCollectionFactory Category factory
     */
    public function __construct(CollectionFactory $categoryCollectionFactory)
    {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Return active categories as options for multiselect.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('level', ['gt' => 1]) // skip root
            ->setOrder('path', 'ASC');

        $options = [];

        foreach ($collection as $category) {
            /**
             * Build label with indentation based on category level.
             *
             * @var \Magento\Catalog\Model\Category $category Category instance
             */
            $label = $category->getName();
            $level = (int)$category->getLevel();

            if ($level > 2) {
                $prefix = str_repeat('— ', $level - 2);
                $label  = $prefix . $label;
            }

            $options[] = [
                'label' => $label,
                'value' => (string)$category->getId(),
            ];
        }

        return $options;
    }
}
