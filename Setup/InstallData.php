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

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

/**
 * @codeCoverageIgnore
 */
class InstallData
    implements InstallDataInterface
{
    /**
     * Category setup factory
     *
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */    
    private $eavSetupFactory;
    
    
    /**
     * Init
     *
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @param \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        CategorySetupFactory $categorySetupFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
	
	/** @var \Magento\Eav\Setup\EavSetup $eavSetup */	
	$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
	
        /** @var \Magento\Catalog\Setup\CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);

        $categorySetup->addAttribute(
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
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            'is_subcat_enabled',
            [
                'type' => 'int',
                'label' => 'Display this Sub Category',
                'input' => 'select',
                'sort_order' => 1,
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global' => 1,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => 1,
                'group' => 'Jscriptz Subcats',
                'backend' => ''
            ]
        );
        $categorySetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY, 'subcat_description', [
		'type' => 'text',
		'label' => 'Subcategory Description (Jscriptz_Subcats)',
		'input' => 'textarea',
		'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
		'required' => false,
		'sort_order' => 100,
		'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
		'visible' => true,
		'user_defined' => false,
		'default' => 0,
		'searchable' => false,
		'filterable' => false,
		'comparable' => false,
		'visible_on_front' => true,
		'used_in_product_listing' => true,
		'is_wysiwyg_enabled'      => true,
		'unique' => false,
		'group' => 'Jscriptz Subcats'
            ]
        );
        $categorySetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY, 'subcat_name', [
		'type' => 'varchar',
		'label' => 'Subcat Name',
		'input' => 'text',
		'required' => false,
		'sort_order' => 1,
		'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
		'visible' => true,
		'user_defined' => false,
		'default' => 0,
		'searchable' => false,
		'filterable' => false,
		'comparable' => false,
		'visible_on_front' => true,
		'used_in_product_listing' => true,
		'unique' => false,
		'group' => 'Jscriptz Subcats'
            ]
        );        
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
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Category::ENTITY,
            'subcats_children',
            [
                'type' => 'text',
                'label' => 'Subcategories to Display',
                'input' => 'multiselect',
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                'required' => false,
                'sort_order' => 30,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'group' => 'Jscriptz Subcats',
                'note' => 'Leave empty to show all active child categories.',
            ]
        );

        $setup->endSetup();
    }
}