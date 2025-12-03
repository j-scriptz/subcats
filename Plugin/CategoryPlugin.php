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


namespace Jscriptz\Subcats\Plugin;

use Magento\Catalog\Model\Category as Subject;

class CategoryPlugin
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Jscriptz\Subcats\Helper\Data
     */
    protected $_helper;

    /**
     * DataProviderPlugin constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Jscriptz\Subcats\Helper\Data $helper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Jscriptz\Subcats\Helper\Data $helper
    ) {
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
    }

    /**
     * Around get data for preprocess image
     *
     * @param Subject $subject
     * @param \Closure $proceed
     * @param string $key
     * @param null $index
     * @return mixed|string
     */
    public function aroundGetData(
        Subject $subject,
        \Closure $proceed,
        $key = '',
        $index = null
    ) {
        if ($key == \Jscriptz\Subcats\Helper\Data::ATTRIBUTE_NAME) {
            $result = $proceed($key, $index);
            if ($result) {
                return $this->_helper->getUrl($result);
            } else {
                return $result;
            }
        }

        return $proceed($key, $index);
    }
}