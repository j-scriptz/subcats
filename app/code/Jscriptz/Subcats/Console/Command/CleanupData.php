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
 */
class CleanupData extends Command
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->moduleDataSetup   = $moduleDataSetup;
        $this->resourceConnection = $resourceConnection;
        $this->logger             = $logger;
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('jscriptz:subcats:cleanup');
        $this->setDescription('Clean Jscriptz_Subcats EAV attributes and configuration values.');
        parent::configure();
    }

    /**
     * Execute cleanup command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting Jscriptz_Subcats cleanup...</info>');

        $connection = $this->moduleDataSetup->getConnection();
        $this->moduleDataSetup->startSetup();
        $connection->beginTransaction();

        try {
            // Remove category attributes
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var EavSetupFactory $eavSetupFactory */
            $eavSetupFactory = $objectManager->get(EavSetupFactory::class);
            $eavSetup = $eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
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
                        sprintf(' - Removing category attribute <comment>%s</comment>', $code)
                    );
                    $eavSetup->removeAttribute($entityTypeId, $code);
                } else {
                    $output->writeln(
                        sprintf(' - Attribute <comment>%s</comment> not found, skipping.', $code)
                    );
                }
            }

            // Remove core_config_data entries
            // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
            $configTable = $this->moduleDataSetup->getTable('core_config_data');
            $deleted = $connection->delete(
                $configTable,
                "path LIKE 'jscriptz_subcats/%' OR path LIKE 'jscriptz/%'"
            );
            $output->writeln(
                sprintf(
                    ' - Removed <comment>%d</comment> row(s) from core_config_data.',
                    $deleted
                )
            );

            // Remove patch_list entries for data patches
            $patchTable = $this->moduleDataSetup->getTable('patch_list');
            if ($connection->isTableExists($patchTable)) {
                $patchDeleted = $connection->delete(
                    $patchTable,
                    "patch_name LIKE 'Jscriptz\\\\Subcats\\\\Setup\\\\Patch\\\\Data\\\\%'"
                );
                $output->writeln(sprintf(
                    ' - Removed <comment>%d</comment> row(s) from patch_list for Jscriptz_Subcats patches.',
                    $patchDeleted
                ));
            }

            $connection->commit();
            $this->moduleDataSetup->endSetup();

            $output->writeln('<info>Jscriptz_Subcats cleanup completed successfully.</info>');
            return Cli::RETURN_SUCCESS;
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->moduleDataSetup->endSetup();

            $this->logger->error(
                'Error during Jscriptz_Subcats cleanup: ' . $e->getMessage(),
                ['exception' => $e]
            );
            $output->writeln('<error>Error during Jscriptz_Subcats cleanup: ' . $e->getMessage() . '</error>');

            return Cli::RETURN_FAILURE;
        }
    }
}
