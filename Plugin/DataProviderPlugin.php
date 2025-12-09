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

use Magento\Catalog\Model\Category\DataProvider as Subject;

/**
 * Plugin DataProviderPlugin
 */
class DataProviderPlugin
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
     * @param Subject $subject
     * @param \Closure $proceed
     * @return array
     */
    public function aroundGetData(
        Subject $subject,
        \Closure $proceed
    ) {
        $result = $proceed();

        $category = $subject->getCurrentCategory();
        if ($category) {
            $categoryData = $category->getData();

            if (isset($categoryData[\Jscriptz\Subcats\Helper\Data::ATTRIBUTE_NAME])) {
                unset($categoryData[\Jscriptz\Subcats\Helper\Data::ATTRIBUTE_NAME]);

                // Try the primary attribute first, then fall back to the legacy one
                $imageValue = $category->getData(\Jscriptz\Subcats\Helper\Data::ATTRIBUTE_NAME);
                if (!$imageValue) {
                    $imageValue = $category->getData(\Jscriptz\Subcats\Helper\Data::ATTRIBUTE_LEGACY_NAME);
                }

                // Normalise stored value so the admin form gets just the filename
                if (is_string($imageValue) && $imageValue !== '') {
                    // Strip any full base media URL that may have been stored
                    $imageValue = preg_replace(
                        '#^https?://[^/]+/media/#i',
                        '',
                        ltrim($imageValue, '/')
                    );

                    // Strip any leading media/catalog/category or catalog/category prefix
                    $imageValue = preg_replace('#^(?:media/)?catalog/category/+?#i', '', $imageValue);
                }

                $result[$category->getId()][\Jscriptz\Subcats\Helper\Data::ATTRIBUTE_NAME] = [
                    [
                        'name' => $imageValue,
                        'url' => $this->_helper->getImageUrl($category),
                    ]
                ];
            }
        }

        return $result;
    }
}
