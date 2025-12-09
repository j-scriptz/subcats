<?php
declare(strict_types=1);

namespace Jscriptz\Subcats\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement as Element;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Jscriptz\Subcats\Model\Config\Source\CategoryMultiselect;

/**
 * Widget parameter renderer for Subcats category selection with ordering.
 *
 * Renders:
 *  - A hidden text input (the real widget param, comma-separated IDs)
 *  - A dual-list UI:
 *      * Available categories (full paths)
 *      * Final order (selected categories) with Move Up / Move Down
 */
class CategoryOrder extends Template
{
    /**
     * @var ElementFactory
     */
    private $elementFactory;

    /**
     * @var CategoryMultiselect
     */
    private $categorySource;

    /**
     * @var Element
     */
    private $element;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ElementFactory $elementFactory
     * @param CategoryMultiselect $categorySource
     * @param array $data
     */
    public function __construct(
        Context             $context,
        ElementFactory      $elementFactory,
        CategoryMultiselect $categorySource,
        array               $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->categorySource = $categorySource;
        parent::__construct($context, $data);
    }

    /**
     * Magento calls this to let us replace the standard field with our own UI.
     */
    public function prepareElementHtml(Element $element): Element
    {
        $this->element = $element;

        // Hidden text input that actually stores the comma-separated IDs
        /** @var \Magento\Framework\Data\Form\Element\Text $input */
        $input = $this->elementFactory->create('text', ['data' => $element->getData()]);
        $input->setId($element->getId());
        $input->setForm($element->getForm());
        $input->setClass('widget-option input-text admin__control-text');
        if ($element->getRequired()) {
            $input->addClass('required-entry');
        }
        // Hide it – we only use it as the backing store for the widget parameter.
        $input->setData('style', 'display:none;');

        $this->setTemplate('Jscriptz_Subcats::widget/category_order.phtml');

        $html = $input->getElementHtml() . $this->toHtml();
        $element->setData('after_element_html', $html);

        return $element;
    }

    /**
     * Expose the element to the template.
     */
    public function getElement(): Element
    {
        return $this->element;
    }

    /**
     * Options for the category list (flattened tree with full path labels).
     *
     * @return array[]
     */
    public function getOptions(): array
    {
        return $this->categorySource->toOptionArray();
    }

    /**
     * Returns selected IDs from the widget param (comma-separated).
     *
     * @return int[]
     */
    public function getSelectedIds(): array
    {
        if (!$this->element) {
            return [];
        }

        $value = $this->element->getValue();

        // Case 1: comma-separated string from stored widget config
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }
            $ids = array_map('intval', explode(',', $value));
        } // Case 2: array from POST / form context
        elseif (is_array($value)) {
            $ids = array_map('intval', $value);
        } // Anything else (null, bool, etc.) → nothing selected
        else {
            return [];
        }

        $ids = array_values(array_filter($ids, static function (int $id): bool {
            return $id > 0;
        }));

        return $ids;
    }
}
