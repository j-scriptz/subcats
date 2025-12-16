<?php
/**
 * CategoryOrder widget parameter renderer file.
 *
 * PHP Version 8+
 *
 * @category Jscriptz
 * @package  Jscriptz_Subcats
 * @author   Jscriptz <support@jscriptz.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://jscriptz.com
 */
declare(strict_types=1);

namespace Jscriptz\Subcats\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement as Element;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Registry;
use Jscriptz\Subcats\Model\Config\Source\CategoryMultiselect;

/**
 * Widget parameter renderer for Subcats category selection with ordering.
 *
 * Renders:
 *  - A hidden text input (the real widget param, comma-separated IDs)
 *  - A dual-list UI:
 *      * Available categories (full paths)
 *      * Final order (selected categories) with Move Up / Move Down
 *
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://jscriptz.com
 */
class CategoryOrder extends Template
{
    /**
     * Element factory for creating form elements.
     *
     * @var ElementFactory
     */
    private $_elementFactory;

    /**
     * Category multiselect source model.
     *
     * @var CategoryMultiselect
     */
    private $_categorySource;

    /**
     * Registry for accessing current widget instance.
     *
     * @var Registry
     */
    private $_registry;

    /**
     * Current form element being rendered.
     *
     * @var Element
     */
    private $_element;

    /**
     * Constructor.
     *
     * @param Context             $context        Template context
     * @param ElementFactory      $elementFactory Element factory instance
     * @param CategoryMultiselect $categorySource Category source model
     * @param Registry            $registry       Registry instance
     * @param array               $data           Block data
     */
    public function __construct(
        Context             $context,
        ElementFactory      $elementFactory,
        CategoryMultiselect $categorySource,
        Registry            $registry,
        array               $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        $this->_categorySource = $categorySource;
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Magento calls this to let us replace the standard field with our own UI.
     *
     * @param Element $element The form element to prepare
     *
     * @return Element
     */
    public function prepareElementHtml(Element $element): Element
    {
        $this->_element = $element;

        /**
         * Hidden text input that stores the comma-separated IDs.
         *
         * @var \Magento\Framework\Data\Form\Element\Text $input
         */
        $input = $this->_elementFactory->create(
            'text',
            ['data' => $element->getData()]
        );
        $input->setId($element->getId());
        $input->setForm($element->getForm());
        $input->setClass('widget-option input-text admin__control-text');
        if ($element->getRequired()) {
            $input->addClass('required-entry');
        }
        // Hide it - backing store for the widget parameter.
        $input->setData('style', 'display:none;');

        $this->setTemplate('Jscriptz_Subcats::widget/category_order.phtml');

        $html = $input->getElementHtml() . $this->toHtml();
        $element->setData('after_element_html', $html);

        return $element;
    }

    /**
     * Expose the element to the template.
     *
     * @return Element
     */
    public function getElement(): Element
    {
        return $this->_element;
    }

    /**
     * Options for the category list (flattened tree with full path labels).
     *
     * @return array[]
     */
    public function getOptions(): array
    {
        $storeId = $this->_getSavedStoreFilter();
        return $this->_categorySource->toOptionArray($storeId);
    }

    /**
     * Get the saved store_filter value from the widget instance.
     *
     * @return int|null
     */
    private function _getSavedStoreFilter(): ?int
    {
        // Get widget instance from registry
        $widgetInstance = $this->_registry->registry(
            'current_widget_instance'
        );
        if ($widgetInstance) {
            $params = $widgetInstance->getWidgetParameters();
            if (isset($params['store_filter'])
                && $params['store_filter'] !== ''
            ) {
                return (int) $params['store_filter'];
            }
        }

        return null;
    }

    /**
     * Get AJAX URL for fetching categories by store.
     *
     * @return string
     */
    public function getCategoriesAjaxUrl(): string
    {
        return $this->getUrl('jscriptz_subcats/widget/categories');
    }

    /**
     * Returns selected IDs from the widget param (comma-separated).
     *
     * @return int[]
     */
    public function getSelectedIds(): array
    {
        if (!$this->_element) {
            return [];
        }

        $value = $this->_element->getValue();

        // Case 1: comma-separated string from stored widget config
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }
            $ids = array_map('intval', explode(',', $value));
        } elseif (is_array($value)) {
            // Case 2: array from POST / form context
            $ids = array_map('intval', $value);
        } else {
            // Anything else (null, bool, etc.) → nothing selected
            return [];
        }

        $ids = array_values(
            array_filter(
                $ids,
                static function (int $id): bool {
                    return $id > 0;
                }
            )
        );

        return $ids;
    }
}
