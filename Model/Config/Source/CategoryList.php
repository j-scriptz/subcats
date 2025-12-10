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
 * @copyright  Copyright (c) 2019-2025 Jscriptz LLC. (https://mage.jscriptz.com)
 * @license    https://mage.jscriptz.com/LICENSE.txt
 */

namespace Jscriptz\Subcats\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Model CategoryList
 */
class CategoryList implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(CollectionFactory $categoryCollectionFactory)
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Return active categories as options for multiselect.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('level', ['gt' => 1]) // skip root
            ->setOrder('path', 'ASC');

        $options = [];

        foreach ($collection as $category) {
            /** @var \Magento\Catalog\Model\Category $category */
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
