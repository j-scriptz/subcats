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
 * PHP version 7
 *
 * @category  Jscriptz
 * @package   Jscriptz_Subcats
 * @author    Jason Lotzer <jasonlotzer@gmail.com>
 * @copyright 2019 Jscriptz LLC
 * @license   https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link      https://mage.jscriptz.com
 * @link      https://mage.jscriptz.com
 */

namespace Jscriptz\Subcats\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Jscriptz\Subcats\Helper\Data;

/**
 * Block Config
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class Config extends Template
{
    /**
     * Helper data instance.
     *
     * @var Data
     */
    protected $helper;

    /**
     * Constructor.
     *
     * @param Context $context Context instance
     * @param Data    $helper  Helper instance
     */
    public function __construct(Context $context, Data $helper)
    {
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Check if module is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->helper->isEnabled();
    }
}
