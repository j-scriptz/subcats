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
 * @copyright  Copyright (c) 2019-2025 Jscriptz LLC. (https://mage.jscriptz.com)
 * @license    https://mage.jscriptz.com/LICENSE.txt
 */

namespace Jscriptz\Subcats\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Model Option
 */
class Option implements ArrayInterface
{
    /**
     * To option array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '12', 'label' => __('1 Column')],
            ['value' => '6',  'label' => __('2 Columns')],
            ['value' => '4',  'label' => __('3 Columns')],
            ['value' => '3',  'label' => __('4 Columns')],
            ['value' => '5',  'label' => __('5 Columns')],
            ['value' => '2',  'label' => __('6 Columns')],
        ];
    }
}
