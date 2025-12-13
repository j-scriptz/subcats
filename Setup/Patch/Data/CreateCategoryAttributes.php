<?php
declare(strict_types=1);

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
 */
class CreateCategoryAttributes implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var CategorySetupFactory
     */
    private CategorySetupFactory $categorySetupFactory;

    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Image attribute (General Information group)
        $this->addAttributeIfMissing(
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
        $this->addAttributeIfMissing(
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
        $this->addAttributeIfMissing(
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

        $this->addAttributeIfMissing(
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
        $this->addAttributeIfMissing(
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

        $this->addAttributeIfMissing(
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
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'group' => 'Jscriptz Subcats',
                    ]
                );
            }
        );

        $this->addAttributeIfMissing(
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
                        'global' => ScopedAttributeInterface::SCOPE_STORE,
                        'group' => 'Jscriptz Subcats',
                    ]
                );
            }
        );

        // Selected children (multiselect stores IDs)
        $this->addAttributeIfMissing(
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
     * @param EavSetup $eavSetup
     * @param string $attributeCode
     * @param callable():void $addCallback
     * @return void
     */
    private function addAttributeIfMissing(EavSetup $eavSetup, string $attributeCode, callable $addCallback): void
    {
        $attributeId = (int) $eavSetup->getAttributeId(Category::ENTITY, $attributeCode);
        if ($attributeId > 0) {
            return;
        }

        $addCallback();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
