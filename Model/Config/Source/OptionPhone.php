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

use \Magento\Framework\Data\OptionSourceInterface;

/**
 * Model OptionPhone
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class OptionPhone implements OptionSourceInterface
{
    /**
     * To option array.
     *
     * @return array
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
