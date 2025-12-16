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

namespace Jscriptz\Subcats\Console\Command;

use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Console\Cli;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI cleanup command for Jscriptz_Subcats.
 *
 * This is primarily for manual installs (app/code) where
 * `bin/magento module:uninstall` is not available. It removes:
 *  - Category EAV attributes created by the module
 *  - core_config_data rows for jscriptz_subcats/* (and legacy jscriptz/*)
 *  - patch_list rows for this module's data patches
 *
 * @category Jscriptz
 * @package  Jscriptz_Subcats
 * @author   Jason Lotzer <jasonlotzer@gmail.com>
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class CleanupData extends Command
{
    /**
     * Module data setup instance.
     *
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $_moduleDataSetup;

    /**
     * Resource connection instance.
     *
     * @var ResourceConnection
     */
    private ResourceConnection $_resourceConnection;

    /**
     * Logger instance.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $_logger;

    /**
     * EAV setup factory.
     *
     * @var EavSetupFactory
     */
    private EavSetupFactory $_eavSetupFactory;

    /**
     * Constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup    Module data setup
     * @param ResourceConnection       $resourceConnection Resource connection
     * @param LoggerInterface          $logger             Logger instance
     * @param EavSetupFactory          $eavSetupFactory    EAV setup factory
     * @param string|null              $name               Command name
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        EavSetupFactory $eavSetupFactory,
        string $name = null
    ) {
        parent::__construct($name);
        $this->_moduleDataSetup    = $moduleDataSetup;
        $this->_resourceConnection = $resourceConnection;
        $this->_logger             = $logger;
        $this->_eavSetupFactory    = $eavSetupFactory;
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('jscriptz:subcats:cleanup');
        $this->setDescription(
            'Clean Jscriptz_Subcats EAV attributes and configuration values.'
        );
        parent::configure();
    }

    /**
     * Execute cleanup command.
     *
     * @param InputInterface  $input  Console input interface
     * @param OutputInterface $output Console output interface
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting Jscriptz_Subcats cleanup...</info>');

        $connection = $this->_moduleDataSetup->getConnection();
        $this->_moduleDataSetup->startSetup();
        $connection->beginTransaction();

        try {
            // Remove category attributes
            $eavSetup = $this->_eavSetupFactory->create(
                ['setup' => $this->_moduleDataSetup]
            );
            $entityTypeId = Category::ENTITY;

            $attributesToRemove = [
                'subcat_image',
                'subcat_name',
                'subcat_description',
                'is_subcat_enabled',
                'subcat_cols_desktop',
                'subcat_cols_tablet',
                'subcat_cols_phone',
                'subcats_children',
            ];

            foreach ($attributesToRemove as $code) {
                $attributeId = $eavSetup->getAttributeId($entityTypeId, $code);
                if ($attributeId) {
                    $output->writeln(
                        sprintf(
                            ' - Removing category attribute <comment>%s</comment>',
                            $code
                        )
                    );
                    $eavSetup->removeAttribute($entityTypeId, $code);
                } else {
                    $output->writeln(
                        sprintf(
                            ' - Attribute <comment>%s</comment> not found, ' .
                            'skipping.',
                            $code
                        )
                    );
                }
            }

            // Remove core_config_data entries
            // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
            $configTable = $this->_moduleDataSetup->getTable('core_config_data');
            $deleted = $connection->delete(
                $configTable,
                "path LIKE 'jscriptz_subcats/%' OR path LIKE 'jscriptz/%'"
            );
            $output->writeln(
                sprintf(
                    ' - Removed <comment>%d</comment> row(s) from ' .
                    'core_config_data.',
                    $deleted
                )
            );

            // Remove patch_list entries for data patches
            $patchTable = $this->_moduleDataSetup->getTable('patch_list');
            if ($connection->isTableExists($patchTable)) {
                $pattern = "patch_name LIKE 'Jscriptz\\\\Subcats\\\\" .
                    "Setup\\\\Patch\\\\Data\\\\%'";
                $patchDeleted = $connection->delete($patchTable, $pattern);
                $output->writeln(
                    sprintf(
                        ' - Removed <comment>%d</comment> row(s) from ' .
                        'patch_list for patches.',
                        $patchDeleted
                    )
                );
            }

            $connection->commit();
            $this->_moduleDataSetup->endSetup();

            $output->writeln(
                '<info>Jscriptz_Subcats cleanup completed successfully.</info>'
            );
            return Cli::RETURN_SUCCESS;
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->_moduleDataSetup->endSetup();

            $this->_logger->error(
                'Error during Jscriptz_Subcats cleanup: ' . $e->getMessage(),
                ['exception' => $e]
            );
            $output->writeln(
                '<error>Error during Jscriptz_Subcats cleanup: ' .
                $e->getMessage() . '</error>'
            );

            return Cli::RETURN_FAILURE;
        }
    }
}
