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
 * PHP version 7
 *
 * @category  Jscriptz
 * @package   Jscriptz_Subcats
 * @author    Jason Lotzer <jasonlotzer@gmail.com>
 * @copyright 2019 Jscriptz LLC
 * @license   https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link      https://mage.jscriptz.com
 */

namespace Jscriptz\Subcats\Plugin;

use Magento\Catalog\Model\Category\DataProvider as Subject;

/**
 * Plugin DataProviderPlugin
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class DataProviderPlugin
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
     * Around get data for preprocess image.
     *
     * @param Subject  $subject Data provider subject
     * @param \Closure $proceed Proceed callable
     *
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

            $attributeName = \Jscriptz\Subcats\Helper\Data::ATTRIBUTE_NAME;
            if (isset($categoryData[$attributeName])) {
                unset($categoryData[$attributeName]);

                // Try the primary attribute first,
                // then fall back to the legacy one
                $imageValue = $category->getData($attributeName);
                if (!$imageValue) {
                    $legacyName
                        = \Jscriptz\Subcats\Helper\Data::ATTRIBUTE_LEGACY_NAME;
                    $imageValue = $category->getData($legacyName);
                }

                // Normalise stored value so the admin form
                // gets just the filename
                if (is_string($imageValue) && $imageValue !== '') {
                    // Strip any full base media URL
                    // that may have been stored
                    $imageValue = preg_replace(
                        '#^https?://[^/]+/media/#i',
                        '',
                        ltrim($imageValue, '/')
                    );

                    // Strip any leading media/catalog/category
                    // or catalog/category prefix
                    $pattern = '#^(?:media/)?catalog/category/+?#i';
                    $imageValue = preg_replace($pattern, '', $imageValue);
                }

                $result[$category->getId()][$attributeName] = [
                    [
                        'name' => $imageValue,
                        'url' => $this->helper->getImageUrl($category),
                    ]
                ];
            }
        }

        return $result;
    }
}
