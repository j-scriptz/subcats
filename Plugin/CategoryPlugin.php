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

namespace Jscriptz\Subcats\Plugin;

use Magento\Catalog\Model\Category as Subject;

/**
 * Plugin CategoryPlugin
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class CategoryPlugin
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Helper
     *
     * @var \Jscriptz\Subcats\Helper\Data
     */
    protected $helper;

    /**
     * DataProviderPlugin constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager Store manager
     * @param \Jscriptz\Subcats\Helper\Data              $helper       Helper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Jscriptz\Subcats\Helper\Data $helper
    ) {
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    /**
     * Around get data for preprocess image
     *
     * @param Subject     $subject Category subject
     * @param \Closure    $proceed Proceed callable
     * @param string      $key     Data key
     * @param string|null $index   Index
     *
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
                return $this->helper->getUrl($result);
            } else {
                return $result;
            }
        }

        return $proceed($key, $index);
    }
}
