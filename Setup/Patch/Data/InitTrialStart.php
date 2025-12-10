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

namespace Jscriptz\Subcats\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Setup InitTrialStart
 */
class InitTrialStart implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * Constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter    = $configWriter;
    }

    /**
     * Set the trial_start config when the module is first installed.
     *
     * @return $this
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');
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
            $this->configWriter->save(
                $path,
                $now->format('Y-m-d'),
                'default',
                0
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get aliases.
     */
    public function getAliases()
    {
        return [];
    }
}
