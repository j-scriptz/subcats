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


namespace Jscriptz\Subcats\Setup;

use Magento\Catalog\Model\Category;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Psr\Log\LoggerInterface;

/**
 * Uninstall script for Jscriptz_Subcats.
 *
 * This will:
 *  - Remove all custom category attributes used by the module
 *  - Remove core_config_data entries for jscriptz_subcats/* (and the legacy jscriptz/* paths)
 *  - Clean patch_list records for this module's data patches
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EavSetupFactory    $eavSetupFactory
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface    $logger
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->eavSetupFactory   = $eavSetupFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger             = $logger;
    }

    /**
     * @inheritdoc
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $connection = $setup->getConnection();
        $setup->startSetup();

        try {
            // Remove custom category attributes
            $eavSetup     = $this->eavSetupFactory->create(['setup' => $setup]);
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
                    $eavSetup->removeAttribute($entityTypeId, $code);
                }
            }

            // Remove core_config_data values for this module
            $configTable = $setup->getTable('core_config_data');
            $connection->delete(
                $configTable,
                "path LIKE 'jscriptz_subcats/%' OR path LIKE 'jscriptz/%'"
            );

            // Remove patch_list entries for this module's patches
            $patchTable = $setup->getTable('patch_list');
            if ($connection->isTableExists($patchTable)) {
                $connection->delete(
                    $patchTable,
                    "patch_name LIKE 'Jscriptz\\\\Subcats\\\\Setup\\\\Patch\\\\Data\\\\%'"
                );
            }
        } catch (\Throwable $e) {
            // Log but don't block uninstall if something goes wrong
            $this->logger->error(
                'Error while uninstalling Jscriptz_Subcats: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }

        $setup->endSetup();
    }
}
