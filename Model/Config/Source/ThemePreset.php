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

use Magento\Framework\Option\ArrayInterface;
use Jscriptz\Subcats\Helper\Data as ConfigHelper;

/**
 * Model ThemePreset
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class ThemePreset implements ArrayInterface
{
    /**
     * Return options for theme presets.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => ConfigHelper::DESIGN_PRESET_THEME_DEFAULT,
                'label' => __('Theme Default'),
            ],
            [
                'value' => ConfigHelper::DESIGN_PRESET_SOFT_CARDS,
                'label' => __('Soft cards'),
            ],
            [
                'value' => ConfigHelper::DESIGN_PRESET_BORDERLESS,
                'label' => __('Borderless grid'),
            ],
            [
                'value' => ConfigHelper::DESIGN_PRESET_LIGHT,
                'label' => __('Light cards'),
            ],
            [
                'value' => ConfigHelper::DESIGN_PRESET_DARK,
                'label' => __('Dark cards'),
            ],
            [
                'value' => ConfigHelper::DESIGN_PRESET_CUSTOM,
                'label' => __('Custom'),
            ],
        ];
    }
}
