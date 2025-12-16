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

use Jscriptz\Subcats\Model\Category\Attribute\Source\OptionsDesktop;
use Jscriptz\Subcats\Model\Category\Attribute\Source\OptionsPhone;
use Jscriptz\Subcats\Model\Category\Attribute\Source\OptionsTablet;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\Attribute\Backend\Image as CategoryImageBackend;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as CatalogEavAttribute;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Create/ensure the Category EAV attributes used by Jscriptz_Subcats.
 *
 * This replaces the legacy InstallData/UpgradeData scripts.
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class CreateCategoryAttributes implements DataPatchInterface
{
    /**
     * Module data setup
     *
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $_moduleDataSetup;

    /**
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    private CategorySetupFactory $_categorySetupFactory;

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private EavSetupFactory $_eavSetupFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup      Module data setup
     * @param CategorySetupFactory     $categorySetupFactory Category setup factory
     * @param EavSetupFactory          $eavSetupFactory      EAV setup factory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->_moduleDataSetup = $moduleDataSetup;
        $this->_categorySetupFactory = $categorySetupFactory;
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Apply the patch
     *
     * @return void
     */
    public function apply(): void
    {
        $connection = $this->_moduleDataSetup->getConnection();
        $connection->startSetup();

        $eavSetup = $this->_eavSetupFactory->create(
            ['setup' => $this->_moduleDataSetup]
        );
        $categorySetup = $this->_categorySetupFactory->create(
            ['setup' => $this->_moduleDataSetup]
        );

        // Image attribute (General Information group)
        $this->_addAttributeIfMissing(
            $eavSetup,
            'subcat_image',
            function () use ($categorySetup): void {
                $categorySetup->addAttribute(
                    'catalog_category',
                    'subcat_image',
                    [
                        'type' => 'varchar',
                        'label' => 'Subcategory Image',
                        'input' => 'image',
                        'backend' => CategoryImageBackend::class,
                        'required' => false,
                        'sort_order' => 2,
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'group' => 'General Information',
                    ]
                );
            }
        );

        // Enable/disable on category (Jscriptz Subcats group)
        $this->_addAttributeIfMissing(
            $eavSetup,
            'is_subcat_enabled',
            function () use ($eavSetup): void {
                $eavSetup->addAttribute(
                    Category::ENTITY,
                    'is_subcat_enabled',
                    [
                        'type' => 'int',
                        'label' => 'Display this Sub Category',
                        'input' => 'select',
                        'sort_order' => 1,
                        'source' => Boolean::class,
                        'global' => 1,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => 1,
                        'group' => 'Jscriptz Subcats',
                        'backend' => '',
                    ]
                );
            }
        );

        // Legacy support fields (not required for most storefront output)
        $this->_addAttributeIfMissing(
            $eavSetup,
            'subcat_description',
            function () use ($eavSetup): void {
                $eavSetup->addAttribute(
                    Category::ENTITY,
                    'subcat_description',
                    [
                        'type' => 'text',
                        'label' => 'Subcategory Description (Jscriptz_Subcats)',
                        'input' => 'textarea',
                        'backend' => ArrayBackend::class,
                        'required' => false,
                        'sort_order' => 100,
                        'global' => CatalogEavAttribute::SCOPE_GLOBAL,
                        'visible' => true,
                        'user_defined' => false,
                        'default' => 0,
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => true,
                        'used_in_product_listing' => true,
                        'is_wysiwyg_enabled' => true,
                        'unique' => false,
                        'group' => 'Jscriptz Subcats',
                    ]
                );
            }
        );

        $this->_addAttributeIfMissing(
            $eavSetup,
            'subcat_name',
            function () use ($eavSetup): void {
                $eavSetup->addAttribute(
                    Category::ENTITY,
                    'subcat_name',
                    [
                        'type' => 'varchar',
                        'label' => 'Subcat Name',
                        'input' => 'text',
                        'required' => false,
                        'sort_order' => 1,
                        'global' => CatalogEavAttribute::SCOPE_GLOBAL,
                        'visible' => true,
                        'user_defined' => false,
                        'default' => 0,
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => true,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'group' => 'Jscriptz Subcats',
                    ]
                );
            }
        );

        // Columns settings
        $this->_addAttributeIfMissing(
            $eavSetup,
            'subcat_cols_desktop',
            function () use ($eavSetup): void {
                $eavSetup->addAttribute(
                    Category::ENTITY,
                    'subcat_cols_desktop',
                    [
                        'type' => 'int',
                        'label' => 'Desktop Columns',
                        'input' => 'select',
                        'source' => OptionsDesktop::class,
                        'required' => false,
                        'sort_order' => 4,
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'group' => 'Jscriptz Subcats',
                    ]
                );
            }
        );

        $this->_addAttributeIfMissing(
            $eavSetup,
            'subcat_cols_tablet',
            function () use ($eavSetup): void {
                $eavSetup->addAttribute(
                    Category::ENTITY,
                    'subcat_cols_tablet',
                    [
                        'type' => 'int',
                        'label' => 'Tablet Columns',
                        'input' => 'select',
                        'source' => OptionsTablet::class,
                        'required' => false,
                        'sort_order' => 5,
                        'default' => 0,
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'group' => 'Jscriptz Subcats',
                    ]
                );
            }
        );

        $this->_addAttributeIfMissing(
            $eavSetup,
            'subcat_cols_phone',
            function () use ($eavSetup): void {
                $eavSetup->addAttribute(
                    Category::ENTITY,
                    'subcat_cols_phone',
                    [
                        'type' => 'int',
                        'label' => 'Smartphone Columns',
                        'input' => 'select',
                        'source' => OptionsPhone::class,
                        'required' => false,
                        'sort_order' => 6,
                        'default' => 0,
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'group' => 'Jscriptz Subcats',
                    ]
                );
            }
        );

        // Selected children (multiselect stores IDs)
        $this->_addAttributeIfMissing(
            $eavSetup,
            'subcats_children',
            function () use ($eavSetup): void {
                $eavSetup->addAttribute(
                    Category::ENTITY,
                    'subcats_children',
                    [
                        'type' => 'text',
                        'label' => 'Subcategories to Display',
                        'input' => 'multiselect',
                        'backend' => ArrayBackend::class,
                        'required' => false,
                        'sort_order' => 30,
                        'default' => 0,
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'visible' => true,
                        'group' => 'Jscriptz Subcats',
                        'note' => 'Leave empty to show all active child categories.',
                    ]
                );
            }
        );

        $connection->endSetup();
    }

    /**
     * Add attribute if it does not exist.
     *
     * @param EavSetup        $eavSetup      EAV setup instance
     * @param string          $attributeCode Attribute code
     * @param callable():void $addCallback   Callback to add attribute
     *
     * @return                                      void
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    private function _addAttributeIfMissing( // @codingStandardsIgnoreLine
        EavSetup $eavSetup,
        string $attributeCode,
        callable $addCallback
    ): void {
        $attributeId = (int) $eavSetup->getAttributeId(
            Category::ENTITY,
            $attributeCode
        );
        if ($attributeId > 0) {
            return;
        }

        $addCallback();
    }

    /**
     * Get dependencies for this patch.
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases for this patch.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }
}
