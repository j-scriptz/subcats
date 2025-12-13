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

namespace Jscriptz\Subcats\Controller\Adminhtml\Widget;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Jscriptz\Subcats\Model\Config\Source\CategoryMultiselect;

/**
 * AJAX controller to fetch categories for a specific store.
 */
class Categories extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CategoryMultiselect
     */
    private $categorySource;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CategoryMultiselect $categorySource
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CategoryMultiselect $categorySource
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->categorySource = $categorySource;
    }

    /**
     * Check admin permissions.
     *
     * @return bool
     */
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

        $options = $this->categorySource->toOptionArray($storeId);

        $result = $this->resultJsonFactory->create();
        return $result->setData([
            'success' => true,
            'options' => $options
        ]);
    }
}
