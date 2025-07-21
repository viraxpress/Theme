<?php
/**
 * ViraXpress - https://www.viraxpress.com
 *
 * LICENSE AGREEMENT
 *
 * This file is part of the ViraXpress package and is licensed under the ViraXpress license agreement.
 * You can view the full license at:
 * https://www.viraxpress.com/license
 *
 * By utilizing this file, you agree to comply with the terms outlined in the ViraXpress license.
 *
 * DISCLAIMER
 *
 * Modifications to this file are discouraged to ensure seamless upgrades and compatibility with future releases.
 *
 * @category    ViraXpress
 * @package     ViraXpress_Theme
 * @author      ViraXpress
 * @copyright   © 2024 ViraXpress (https://www.viraxpress.com/)
 * @license     https://www.viraxpress.com/license
 */

namespace ViraXpress\Theme\Block\Html;

use Magento\Framework\View\DesignInterface;
use Magento\Theme\Block\Html\Footer as BaseFooter;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template\Context;

/**
 * Html page footer block
 *
 * @api
 * @since 100.0.2
 */
class Footer extends \Magento\Theme\Block\Html\Footer
{
    /**
     * Copyright information
     *
     * @var string
     */
    protected $_copyright;

    /**
     * Miscellaneous HTML information
     *
     * @var string
     */
    private $miscellaneousHtml;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var DesignInterface
     */
    protected $design;

    /**
     * @param Context $context
     * @param HttpContext $httpContext
     * @param DesignInterface $design
     * @param array $data
     */
    public function __construct(
        Context $context,
        HttpContext $httpContext,
        DesignInterface $design,
        array $data = []
    ) {
        parent::__construct($context, $httpContext, $data);
        $this->design  = $design;
    }

    /**
     * Retrieve copyright information
     *
     * @return string
     */
    public function getCopyright()
    {
        $currentTheme = $this->getCurrentTheme();
        if (!$this->_copyright) {
            $ViraXpressCopyright = $this->_scopeConfig->getValue(
                'viraxpress_config/footer/copyright_footer',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if (!empty($ViraXpressCopyright) && $currentTheme == 'Theme/theme_child') {
                $this->_copyright = $ViraXpressCopyright;
            } else {
                $this->_copyright = $this->_scopeConfig->getValue('design/footer/copyright', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            }
        }
        return __($this->_copyright);
    }

    /**
     * Retrieve current theme
     *
     * @return string
     */
    public function getCurrentTheme()
    {
        $currentTheme = $this->design->getDesignTheme()->getCode();
        return $currentTheme;
    }
}
