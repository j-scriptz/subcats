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

namespace Jscriptz\Subcats\Controller\Adminhtml\Widget;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Jscriptz\Subcats\Model\Config\Source\CategoryMultiselect;

/**
 * AJAX controller to fetch categories for a specific store.
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class Categories extends Action
{
    /**
     * JSON result factory
     *
     * @var JsonFactory
     */
    private $_resultJsonFactory;

    /**
     * Category source model
     *
     * @var CategoryMultiselect
     */
    private $_categorySource;

    /**
     * Constructor.
     *
     * @param Context             $context           Action context
     * @param JsonFactory         $resultJsonFactory JSON result factory
     * @param CategoryMultiselect $categorySource    Category source model
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CategoryMultiselect $categorySource
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_categorySource = $categorySource;
    }

    /**
     * Check admin permissions.
     *
     * @return bool
     */
    // @codingStandardsIgnoreLine
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Widget::widget_instance');
    }

    /**
     * Execute action - return categories for specified store.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $storeId = (int)$this->getRequest()->getParam('store_id', 0);

        $options = $this->_categorySource->toOptionArray($storeId);

        $result = $this->_resultJsonFactory->create();
        return $result->setData(
            [
            'success' => true,
            'options' => $options
            ]
        );
    }
}
