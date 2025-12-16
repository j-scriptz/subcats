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


namespace Jscriptz\Subcats\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Model HoverScale
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class HoverScale implements ArrayInterface
{
    /**
     * To option array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'none',   'label' => __('None')],
            ['value' => 'subtle', 'label' => __('Subtle (1.02)')],
            ['value' => 'medium', 'label' => __('Medium (1.05)')],
            ['value' => 'bold',   'label' => __('Bold (1.08)')],
        ];
    }
}
