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

namespace Jscriptz\Subcats\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Setup InitTrialStart
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class InitTrialStart implements DataPatchInterface
{
    /**
     * Module data setup instance
     *
     * @var ModuleDataSetupInterface
     */
    private $_moduleDataSetup;

    /**
     * Config writer instance
     *
     * @var WriterInterface
     */
    private $_configWriter;

    /**
     * Constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup Module data setup instance
     * @param WriterInterface          $configWriter    Config writer instance
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter
    ) {
        $this->_moduleDataSetup = $moduleDataSetup;
        $this->_configWriter    = $configWriter;
    }

    /**
     * Set the trial_start config when the module is first installed.
     *
     * @return $this
     */
    public function apply()
    {
        $this->_moduleDataSetup->getConnection()->startSetup();

        $connection = $this->_moduleDataSetup->getConnection();
        $configTable = $this->_moduleDataSetup->getTable('core_config_data');
        $path = 'jscriptz_subcats/license/trial_start';

        // Check if we already have a value (default scope)
        $select = $connection->select()
            ->from($configTable, ['value'])
            ->where('path = ?', $path)
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', 0);

        $existing = $connection->fetchOne($select);

        if ($existing === false || $existing === null || $existing === '') {
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            // This writes a row into core_config_data for default scope
            $this->_configWriter->save(
                $path,
                $now->format('Y-m-d'),
                'default',
                0
            );
        }

        $this->_moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Get dependencies.
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get aliases.
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}
