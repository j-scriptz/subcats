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

namespace Jscriptz\Subcats\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Store views source model for widget parameters.
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class StoreViews implements OptionSourceInterface
{
    /**
     * Store manager instance.
     *
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Constructor.
     *
     * @param StoreManagerInterface $storeManager Store manager instance
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->_storeManager = $storeManager;
    }

    /**
     * Return options array for store views.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->_storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                foreach ($group->getStores() as $store) {
                    $options[] = [
                        'value' => $store->getId(),
                        'label' => $website->getName() . ' / ' . $group->getName() . ' / ' . $store->getName()
                    ];
                }
            }
        }

        return $options;
    }
}
