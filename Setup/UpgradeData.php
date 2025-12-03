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
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        // v0.0.8: rename legacy 'additional_image' attribute to 'subcat_image' if present
        $entityType = Category::ENTITY;
        $legacyAttribute = $eavSetup->getAttribute($entityType, 'additional_image');
        $newAttribute    = $eavSetup->getAttribute($entityType, 'subcat_image');

        if (is_array($legacyAttribute) && !empty($legacyAttribute) && empty($newAttribute)) {
            $eavSetup->updateAttribute(
                $entityType,
                'additional_image',
                'attribute_code',
                'subcat_image'
            );
        }

        // Add subcat_image attribute on upgrade if it does not exist yet
        $attributeCode = 'subcat_image';
        $entityType    = Category::ENTITY;
        $attributeId   = $eavSetup->getAttributeId($entityType, $attributeCode);

        if (!$attributeId) {
            $eavSetup->addAttribute(
                'catalog_category',
                'subcat_image',
                [
                    'type' => 'varchar',
                    'label' => 'Subcategory Image',
                    'input' => 'image',
                    'backend' => 'Magento\Catalog\Model\Category\Attribute\Backend\Image',
                    'required' => false,
                    'sort_order' => 2,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'General Information',
                ]
            );
        }

        // Add subcats_children attribute on upgrade if it does not exist yet
        $attributeCode = 'subcats_children';
        $entityType    = Category::ENTITY;
        $attributeId   = $eavSetup->getAttributeId($entityType, $attributeCode);

        if (!$attributeId) {
            $eavSetup->addAttribute(
                $entityType,
                $attributeCode,
                [
                    'type' => 'text',
                    'label' => 'Subcategories to Display',
                    'input' => 'multiselect',
                    'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                    'required' => false,
                    'sort_order' => 30,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'group' => 'Jscriptz Subcats',
                    'note' => 'Leave empty to show all active child categories.',
                ]
            );
        }

        $attributeCode = 'subcats_cols_desktop';
        $entityType    = Category::ENTITY;
        $attributeId   = $eavSetup->getAttributeId($entityType, $attributeCode);

        if(!$attributeId) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'subcat_cols_desktop',
                [
                    'type' => 'int',
                    'label' => 'Desktop Columns',
                    'input' => 'select',
                    'source' => 'Jscriptz\Subcats\Model\Category\Attribute\Source\OptionsDesktop',
                    'required' => false,
                    'sort_order' => 4,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Jscriptz Subcats',
                ]
            );
        }

        $attributeCode = 'subcats_cols_tablet';
        $entityType    = Category::ENTITY;
        $attributeId   = $eavSetup->getAttributeId($entityType, $attributeCode);

        if(!$attributeId) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'subcat_cols_tablet',
                [
                    'type' => 'int',
                    'label' => 'Tablet Columns',
                    'input' => 'select',
                    'source' => 'Jscriptz\Subcats\Model\Category\Attribute\Source\OptionsTablet',
                    'required' => false,
                    'sort_order' => 5,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Jscriptz Subcats',
                ]
            );
        }

        $attributeCode = 'subcats_cols_phone';
        $entityType    = Category::ENTITY;
        $attributeId   = $eavSetup->getAttributeId($entityType, $attributeCode);

        if(!$attributeId) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'subcat_cols_phone',
                [
                    'type' => 'int',
                    'label' => 'Smartphone Columns',
                    'input' => 'select',
                    'source' => 'Jscriptz\Subcats\Model\Category\Attribute\Source\OptionsPhone',
                    'required' => false,
                    'sort_order' => 6,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Jscriptz Subcats',
                ]
            );
        }

        

        $setup->endSetup();
    }
}
