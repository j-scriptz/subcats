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


namespace Jscriptz\Subcats\Block\Adminhtml\Category;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Block SubcatsChildren
 */
class SubcatsChildren extends Template implements RendererInterface
{
    /**
     * Template file for rendering the sortable selected categories list.
     *
     * @var string
     */
    protected $_template = 'Jscriptz_Subcats::category/subcats/children.phtml';

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * SubcatsChildren constructor.
     *
     * @param Template\Context $context
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CategoryCollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Render field using custom template.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    /**
     * @return AbstractElement|null
     */
    public function getElement()
    {
        return $this->getData('element');
    }

    /**
     * @param AbstractElement $element
     * @return $this
     */
    public function setElement(AbstractElement $element)
    {
        return $this->setData('element', $element);
    }

    /**
     * Load selected categories for preview / sorting in the admin UI.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getSelectedCategories()
    {
        /** @var AbstractElement $element */
        $element = $this->getElement();
        if (!$element) {
            return $this->categoryCollectionFactory->create();
        }

        $rawValue = $element->getValue();

        if (is_array($rawValue)) {
            $ids = array_map('intval', $rawValue);
        } else {
            $value = (string)$rawValue;
            $ids = array_filter(array_map('intval', explode(',', $value)));
        }

        $collection = $this->categoryCollectionFactory->create();

        if (!$ids) {
            // Return empty collection if nothing is selected
            $collection->addAttributeToFilter('entity_id', ['eq' => -1]);
            return $collection;
        }

        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('entity_id', ['in' => $ids])
            ->addIsActiveFilter();

        // Keep preview order consistent with stored order
        $collection->getSelect()->order(
            new \Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $ids) . ')')
        );

        return $collection;
    }
}
