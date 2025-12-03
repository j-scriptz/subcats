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

use Magento\Framework\Option\ArrayInterface;

class HoverScale implements ArrayInterface
{
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
