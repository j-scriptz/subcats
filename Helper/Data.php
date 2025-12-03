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


namespace Jscriptz\Subcats\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Data
    extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Primary category attribute code for Jscriptz Subcats image.
     *
     * We used to store this under "additional_image". That legacy code is
     * still supported via ATTRIBUTE_LEGACY_NAME for backward compatibility.
     */
    const ATTRIBUTE_NAME = "subcat_image";
    const ATTRIBUTE_LEGACY_NAME = "additional_image";


    const XML_PATH_DESIGN_LINK_COLOR      = 'jscriptz_subcats/design/link_color';
    const XML_PATH_DESIGN_CARD_RADIUS     = 'jscriptz_subcats/design/card_radius';
    const XML_PATH_DESIGN_CARD_BORDER     = 'jscriptz_subcats/design/card_border';
    const XML_PATH_DESIGN_CARD_PADDING    = 'jscriptz_subcats/design/card_padding';
    const XML_PATH_DESIGN_CARD_BACKGROUND = 'jscriptz_subcats/design/card_background';
    const XML_PATH_DESIGN_USE_PRODUCT_FALLBACK = 'jscriptz_subcats/design/use_product_image_fallback';
    const XML_PATH_DESIGN_IMAGE_BORDER    = 'jscriptz_subcats/design/image_border';
    const XML_PATH_DESIGN_NAME_FONT_SIZE        = 'jscriptz_subcats/design/name_font_size';
    const XML_PATH_DESIGN_NAME_FONT_WEIGHT        = 'jscriptz_subcats/design/name_font_weight';
    const XML_PATH_DESIGN_DESCRIPTION_FONT_SIZE = 'jscriptz_subcats/design/description_font_size';
    const XML_PATH_DESIGN_CARD_SHADOW           = 'jscriptz_subcats/design/card_shadow';
    const XML_PATH_DESIGN_CARD_HOVER_SHADOW = 'jscriptz_subcats/design/card_hover_shadow';
    const XML_PATH_DESIGN_CARD_HOVER_SCALE  = 'jscriptz_subcats/design/card_hover_scale';

    const XML_PATH_DESIGN_TRANSITION_CARD  = 'jscriptz_subcats/design/transition_card';
    const XML_PATH_DESIGN_TRANSITION_IMAGE = 'jscriptz_subcats/design/transition_image';
    const XML_PATH_DESIGN_TRANSITION_TEXT  = 'jscriptz_subcats/design/transition_text';

    const XML_PATH_DESIGN_PRESET           = 'jscriptz_subcats/design/theme_preset';

    const DESIGN_PRESET_THEME_DEFAULT = 'default';
    const DESIGN_PRESET_SOFT_CARDS    = 'soft';
    const DESIGN_PRESET_BORDERLESS    = 'borderless';
    const DESIGN_PRESET_LIGHT         = 'light';
    const DESIGN_PRESET_DARK          = 'dark';
    const DESIGN_PRESET_CUSTOM        = 'custom';

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */   
     protected $_storeManager;
     
     /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    public function __construct(
	\Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor
    ) {
    	 
        $this->_storeManager = $storeManager;
        $this->encryptor = $encryptor;
        
         parent::__construct($context);
        
        
    }

    /**
     * Retrieve image URL by category
     *
     * @return string
     */
    public function getImageUrl(\Magento\Catalog\Model\Category $category)
    {
        // Prefer the primary subcat image attribute, but gracefully fall back
        // to the legacy "additional_image" attribute if needed.
        $image = $category->getData(self::ATTRIBUTE_NAME);
        if (!$image) {
            $image = $category->getData(self::ATTRIBUTE_LEGACY_NAME);
        }
        
        return $this->getUrl($image);
    }


    /**
     * Retrieve URL
     *
     * @return string
     */
    public function getUrl($value)
    {
        if (!$value) {
            return false;
        }

        if (!is_string($value)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while getting the image url.')
            );
        }

        // Already a full URL? Just return it.
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        // Normalise leading slashes
        $value = ltrim($value, '/');

        // Strip any leading "media/catalog/category" or "catalog/category" so we don't duplicate paths.
        // Handles values like:
        //  - media/catalog/category/foo.jpg
        //  - catalog/category/foo.jpg
        //  - /media/catalog/category//foo.jpg
        $value = preg_replace('#^(?:media/)?catalog/category/+?#i', '', $value);

        $baseMediaUrl = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );

        return $baseMediaUrl . 'catalog/category/' . ltrim($value, '/');
    }

    /*
     * @return bool
     */
    public function isEnabled($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->isSetFlag(
            'jscriptz_subcats/general/enabled',
            $scope
        );
    }
    /*
     * @return string
     */
    public function getSubcatName($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'jscriptz_subcats/design/subcat_name',
            $scope
        );
    }
    /*
     * @return string
     */
    public function getSubcatImageWidth($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'jscriptz_subcats/design/subcat_width',
            $scope
        );
    }
    /*
     * @return string
     */
    public function getSubcatImageHeight($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'jscriptz_subcats/design/subcat_height',
            $scope
        );
    }
    /*
     * @return string
     */
    public function getSecret($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        $secret = $this->scopeConfig->getValue(
            'jscriptz_subcats/general/secret',
            $scope
        );
        $secret = $this->encryptor->decrypt($secret);
        
        return $secret;
    }
    /*
     * @return string
     */
    public function getGrowEnabled($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        $grow = $this->scopeConfig->isSetFlag(
            'jscriptz_subcats/design/grow_enabled',
            $scope
        );

        return $grow;
    }

    /**
     * Desktop column width (Bootstrap 12-grid span: 12, 6, 4, 3, 2).
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getOptionDesktop($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'jscriptz_subcats/design/style_option_desktop',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Tablet column width (Bootstrap 12-grid span).
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getOptionTablet($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'jscriptz_subcats/design/style_option_tablet',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Phone column width (Bootstrap 12-grid span).
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getOptionPhone($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'jscriptz_subcats/design/style_option_phone',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }






    /**
     * Design preset key for current store.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDesignPreset($storeId = null)
    {
        $value = (string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_PRESET,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value === '') {
            $value = self::DESIGN_PRESET_THEME_DEFAULT;
        }

        return $value;
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignLinkColor($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_LINK_COLOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $value !== '' ? $value : null;
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignCardRadius($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_CARD_RADIUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $value !== '' ? $value : null;
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignCardBorder($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_CARD_BORDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $value !== '' ? $value : null;
    }

    public function getDesignCardPadding($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_CARD_PADDING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($value === '') {
            return null;
        }

        // If it's just a number (e.g. "10"), treat it as "10px"
        if (preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            $value .= 'px';
        }

        return $value;
    }

    /**
     * Title font size (subcategory name).
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignNameFontSize($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_NAME_FONT_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($value === '') {
            return null;
        }

        // If it's just a number, treat as px
        if (preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            $value .= 'px';
        }

        return $value;
    }
    /**
     * Title font weight (subcategory name).
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignNameFontWeight($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_NAME_FONT_WEIGHT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($value === '') {
            return null;
        }

        return $value;
    }
    /**
     * Description font size.
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignDescriptionFontSize($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_DESCRIPTION_FONT_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($value === '') {
            return null;
        }

        // If it's just a number, treat as px
        if (preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            $value .= 'px';
        }

        return $value;
    }

    /**
     * Card shadow (CSS box-shadow).
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignCardShadow($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_CARD_SHADOW,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $value !== '' ? $value : null;
    }

    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignCardBackground($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_CARD_BACKGROUND,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $value !== '' ? $value : null;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isProductImageFallbackEnabled($storeId = null)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_USE_PRODUCT_FALLBACK,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }


    /**
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignImageBorder($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_IMAGE_BORDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $value !== '' ? $value : null;
    }
    /**
     * Hover shadow preset (none/light/medium/strong).
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignCardHoverShadow($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_CARD_HOVER_SHADOW,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $value !== '' ? $value : null;
    }

    /**
     * Hover scale preset (none/subtle/medium/bold).
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getDesignCardHoverScale($storeId = null)
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_CARD_HOVER_SCALE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return $value !== '' ? $value : null;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isDesignTransitionCardEnabled($storeId = null)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_TRANSITION_CARD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isDesignTransitionImageEnabled($storeId = null)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_TRANSITION_IMAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isDesignTransitionTextEnabled($storeId = null)
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DESIGN_TRANSITION_TEXT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }


}
