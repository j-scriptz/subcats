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

namespace Jscriptz\Subcats\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Store views source model for widget parameters.
 */
class StoreViews implements OptionSourceInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor.
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Return options array for store views.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->storeManager->getWebsites() as $website) {
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
