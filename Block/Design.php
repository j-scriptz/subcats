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


namespace Jscriptz\Subcats\Block;

use Magento\Framework\View\Element\Template;
use Jscriptz\Subcats\Helper\Data as ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;

/**
 * Block Design
 */
class Design extends Template
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var CategoryRepositoryInterface */
    private $categoryRepository;

    /**
     * Constructor.
     *
     * @param Template\Context $context
     * @param ConfigHelper $configHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ConfigHelper $configHelper,
        CategoryRepositoryInterface $categoryRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configHelper = $configHelper;
        $this->scopeConfig  = $context->getScopeConfig();
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Build CSS string with CSS variables based on config and theme preset.
     *
     * @return string
     */
    public function getCss()
    {
        $preset = $this->configHelper->getDesignPreset();
        $vars   = [];
        $hoverShadowPreset = $this->configHelper->getDesignCardHoverShadow();
        $hoverScalePreset  = $this->configHelper->getDesignCardHoverScale();


        // Map shadow preset → actual box-shadow value
        switch ($hoverShadowPreset) {
            case 'light':
                $hoverShadow = '0 4px 12px rgba(0,0,0,0.15)';
                break;
            case 'medium':
                $hoverShadow = '0 8px 20px rgba(0,0,0,0.25)';
                break;
            case 'strong':
                $hoverShadow = '0 12px 30px rgba(0,0,0,0.35)';
                break;
            case 'none':
            default:
                $hoverShadow = 'none';
                break;
        }

        // Map scale preset → numeric factor
        switch ($hoverScalePreset) {
            case 'subtle':
                $hoverScale = '1.02';
                break;
            case 'medium':
                $hoverScale = '1.05';
                break;
            case 'bold':
                $hoverScale = '1.08';
                break;
            case 'none':
            default:
                $hoverScale = '1';
                break;
        }
        // Preset defaults
        switch ($preset) {
            case ConfigHelper::DESIGN_PRESET_SOFT_CARDS:
                $vars['--js-subcats-card-padding']    = '1rem';
                $vars['--js-subcats-card-spacing']    = '1.25rem';
                $vars['--js-subcats-card-radius']     = '0.75rem';
                $vars['--js-subcats-card-border']     = '1px solid #e5e5e5';
                $vars['--js-subcats-card-background'] = '#ffffff';
                break;

            case ConfigHelper::DESIGN_PRESET_BORDERLESS:
                $vars['--js-subcats-card-padding']    = '0.75rem';
                $vars['--js-subcats-card-spacing']    = '1.25rem';
                $vars['--js-subcats-card-radius']     = '0';
                $vars['--js-subcats-card-border']     = 'none';
                $vars['--js-subcats-card-background'] = 'transparent';
                break;

            case ConfigHelper::DESIGN_PRESET_LIGHT:
                $vars['--js-subcats-card-padding']           = '1rem';
                $vars['--js-subcats-card-spacing']           = '1.25rem';
                $vars['--js-subcats-card-radius']            = '0.5rem';
                $vars['--js-subcats-card-border']            = '1px solid #e5e5e5';
                $vars['--js-subcats-card-background']        = '#f9fafb';
                $vars['--js-subcats-image-border']           = '1px solid #e5e7eb';
                $vars['--js-subcats-link-color']             = '#111827';
                $vars['--js-subcats-description-color']      = '#4b5563';
                break;

            case ConfigHelper::DESIGN_PRESET_DARK:
                $vars['--js-subcats-card-padding']           = '1rem';
                $vars['--js-subcats-card-spacing']           = '1.25rem';
                $vars['--js-subcats-card-radius']            = '0.5rem';
                $vars['--js-subcats-card-border']            = '1px solid #111827';
                $vars['--js-subcats-card-background']        = '#111827';
                $vars['--js-subcats-image-border']           = '1px solid #1f2933';
                $vars['--js-subcats-link-color']             = '#f9fafb';
                $vars['--js-subcats-description-color']      = '#e5e7eb';
                $vars['--js-subcats-card-shadow']            = '0 12px 30px rgba(0, 0, 0, 0.5)';
                break;

            case ConfigHelper::DESIGN_PRESET_THEME_DEFAULT:
            case ConfigHelper::DESIGN_PRESET_CUSTOM:
            default:
                // Do not set any base variables; rely on theme / explicit overrides
                break;
        }

        // Explicit overrides from design config
        if (($color = $this->configHelper->getDesignLinkColor()) !== null) {
            $vars['--js-subcats-link-color'] = $color;
        }
        if (($radius = $this->configHelper->getDesignCardRadius()) !== null) {
            $vars['--js-subcats-card-radius'] = $radius;
        }
        if (($border = $this->configHelper->getDesignCardBorder()) !== null) {
            $vars['--js-subcats-card-border'] = $border;
        }
        if (($padding = $this->configHelper->getDesignCardPadding()) !== null) {
            $vars['--js-subcats-card-spacing'] = $padding;
        }
        if (($bg = $this->configHelper->getDesignCardBackground()) !== null) {
            $vars['--js-subcats-card-background'] = $bg;
        }
        if (($imgBorder = $this->configHelper->getDesignImageBorder()) !== null) {
            $vars['--js-subcats-image-border'] = $imgBorder;
        }
        if (($shadow = $this->configHelper->getDesignCardShadow()) !== null) {
            $vars['--js-subcats-card-shadow'] = $shadow;
        }
        if (($nameSize = $this->configHelper->getDesignNameFontSize()) !== null) {
            $vars['--js-subcats-name-font-size'] = $nameSize;
        }
        if (($nameWeight = $this->configHelper->getDesignNameFontWeight()) !== null) {
            $vars['--js-subcats-name-font-weight'] = $nameWeight;
        }
        if (($descSize = $this->configHelper->getDesignDescriptionFontSize()) !== null) {
            $vars['--js-subcats-description-font-size'] = $descSize;
        }

        $desktopSpan = (int)$this->configHelper->getOptionDesktop();
        $tabletSpan  = (int)$this->configHelper->getOptionTablet();
        $phoneSpan   = (int)$this->configHelper->getOptionPhone();

        try {
            $categoryId = (int)$this->getRequest()->getParam('id'); // catalog/category/view?id=...
            if ($categoryId) {
                $storeId  = (int)$this->_storeManager->getStore()->getId();
                $category = $this->categoryRepository->get($categoryId, $storeId);

                $catDesktop = (int)$category->getData('subcat_cols_desktop');
                $catTablet  = (int)$category->getData('subcat_cols_tablet');
                $catPhone   = (int)$category->getData('subcat_cols_phone');

                if ($catDesktop > 0) {
                    $desktopSpan = $catDesktop;
                }
                if ($catTablet > 0) {
                    $tabletSpan  = $catTablet;
                }
                if ($catPhone > 0) {
                    $phoneSpan   = $catPhone;
                }
            }
        } catch (\Exception $e) {
            // If anything goes wrong, just fall back to the global config
        }

        if ($desktopSpan > 0) {
            if ($desktopSpan === 5) {
                // Special case: 5 columns (20% width, 4 gaps)
                $desktopCols = 5;
                $desktopGaps = 4;

                $vars['--js-subcats-col-width-desktop'] = sprintf(
                    'calc((100%% - (var(--js-subcats-card-spacing, 20px) * %d)) / %d)',
                    $desktopGaps,
                    $desktopCols
                );
            } elseif (12 % $desktopSpan === 0) {
                $desktopCols = 12 / $desktopSpan;        // e.g. 4 -> 3 columns
                $desktopGaps = max(0, $desktopCols - 1); // 3 columns -> 2 gaps

                $vars['--js-subcats-col-width-desktop'] = sprintf(
                    'calc((100%% - (var(--js-subcats-card-spacing, 20px) * %d)) / %d)',
                    $desktopGaps,
                    $desktopCols
                );
            } else {
                // Fallback: behave like the old behavior if something weird is configured
                $vars['--js-subcats-col-width-desktop'] = sprintf('%.6f%%', 100 * ($desktopSpan / 12));
            }
        }

        if ($tabletSpan > 0) {
            if ($tabletSpan === 5) {
                $tabletCols = 5;
                $tabletGaps = 4;

                $vars['--js-subcats-col-width-tablet'] = sprintf(
                    'calc((100%% - (var(--js-subcats-card-spacing, 20px) * %d)) / %d)',
                    $tabletGaps,
                    $tabletCols
                );
            } elseif (12 % $tabletSpan === 0) {
                $tabletCols = 12 / $tabletSpan;
                $tabletGaps = max(0, $tabletCols - 1);

                $vars['--js-subcats-col-width-tablet'] = sprintf(
                    'calc((100%% - (var(--js-subcats-card-spacing, 20px) * %d)) / %d)',
                    $tabletGaps,
                    $tabletCols
                );
            } else {
                $vars['--js-subcats-col-width-tablet'] = sprintf('%.6f%%', 100 * ($tabletSpan / 12));
            }
        }

        if ($phoneSpan > 0) {
            if ($phoneSpan === 5) {
                $phoneCols = 5;
                $phoneGaps = 4;

                $vars['--js-subcats-col-width-phone'] = sprintf(
                    'calc((100%% - (var(--js-subcats-card-spacing, 20px) * %d)) / %d)',
                    $phoneGaps,
                    $phoneCols
                );
            } elseif (12 % $phoneSpan === 0) {
                $phoneCols = 12 / $phoneSpan;
                $phoneGaps = max(0, $phoneCols - 1);

                $vars['--js-subcats-col-width-phone'] = sprintf(
                    'calc((100%% - (var(--js-subcats-card-spacing, 20px) * %d)) / %d)',
                    $phoneGaps,
                    $phoneCols
                );
            } else {
                $vars['--js-subcats-col-width-phone'] = sprintf('%.6f%%', 100 * ($phoneSpan / 12));
            }
        }

        if (($shadow = $this->configHelper->getDesignCardShadow()) !== null) {
            $vars['--js-subcats-card-shadow'] = $shadow;
        }

        // Hover shadow preset
        if (($hoverShadowPreset = $this->configHelper->getDesignCardHoverShadow()) !== null) {
            switch ($hoverShadowPreset) {
                case 'light':
                    $vars['--js-subcats-hover-shadow'] = '0 4px 12px rgba(0,0,0,0.15)';
                    break;
                case 'medium':
                    $vars['--js-subcats-hover-shadow'] = '0 8px 20px rgba(0,0,0,0.25)';
                    break;
                case 'strong':
                    $vars['--js-subcats-hover-shadow'] = '0 12px 30px rgba(0,0,0,0.35)';
                    break;
                case 'none':
                default:
                    $vars['--js-subcats-hover-shadow'] = 'none';
                    break;
            }
        }

        // Hover scale preset
        if (($hoverScalePreset = $this->configHelper->getDesignCardHoverScale()) !== null) {
            switch ($hoverScalePreset) {
                case 'subtle':
                    $vars['--js-subcats-hover-scale'] = '1.02';
                    break;
                case 'medium':
                    $vars['--js-subcats-hover-scale'] = '1.05';
                    break;
                case 'bold':
                    $vars['--js-subcats-hover-scale'] = '1.08';
                    break;
                case 'none':
                default:
                    $vars['--js-subcats-hover-scale'] = '1';
                    break;
            }
        }

        // Transitions are always driven by config toggles
        $vars['--js-subcats-transition-card'] = $this->configHelper->isDesignTransitionCardEnabled()
            ? 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out'
            : 'none';

        $vars['--js-subcats-transition-image'] = $this->configHelper->isDesignTransitionImageEnabled()
            ? 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out'
            : 'none';

        $vars['--js-subcats-transition-text'] = $this->configHelper->isDesignTransitionTextEnabled()
            ? 'color 0.2s ease-in-out'
            : 'none';

        if (empty($vars)) {
            return '';
        }

        $lines = [];
        foreach ($vars as $name => $value) {
            $lines[] = sprintf('    %s: %s;', $name, $value);
        }

        return ".jscriptz-subcats {"
            . implode("\n", $lines) . "\n"
                . "  --js-subcats-hover-shadow: {$hoverShadow};\n"
                . "  --js-subcats-hover-scale: {$hoverScale};\n"
            . "}";
    }
}
