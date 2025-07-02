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

namespace ViraXpress\Theme\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use ViraXpress\Configuration\Helper\Data;

class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * @var PageRepositoryInterface
     */
    protected $pageRepository;

    /**
     * Constructor
     *
     * @param Data $dataHelper
     * @param Template\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param PageRepositoryInterface $pageRepository
     * @param array $data
     */
    public function __construct(
        Data $dataHelper,
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        PageRepositoryInterface $pageRepository,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->scopeConfig = $scopeConfig;
        $this->pageRepository = $pageRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get Configuration Value
     *
     * @param string $config_path Configuration Path
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue($config_path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if the current theme or its parent matches the specified theme path.
     *
     * @return mixed
     */
    public function checkThemePath()
    {
        return $this->dataHelper->checkThemePath();
    }

    /**
     * Get the home page identifier
     *
     * @return string
     */
    public function getHomePageIdentifier()
    {
        return $this->getConfig('web/default/cms_home_page');
    }

    /**
     * Get home page content
     *
     * @return string|null
     */
    public function getHomePageContent()
    {
        $homePageIdentifier = $this->getHomePageIdentifier();
        try {
            $page = $this->pageRepository->getById($homePageIdentifier);
            return $page->getContent(); // Get the CMS content
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Extract background images (desktop and mobile) from the home page content
     *
     * @return array
     */
    public function getHomePageSlideImages()
    {
        $content = $this->getHomePageContent();
        $images = [];

        if ($content) {
            // Extract desktop background image (e.g., inline CSS style)
            preg_match('/background-image:\s*url\((\'|")?(.*?)\1\)/', $content, $desktopMatches);
            if (isset($desktopMatches[2])) {
                $images['desktop'] = $desktopMatches[2];
            }

            // Extract mobile background image (if it has a specific mobile background style)
            preg_match('/background-mobile-image:\s*url\((\'|")?(.*?)\1\)/', $content, $mobileMatches);
            if (isset($mobileMatches[2])) {
                $images['mobile'] = $mobileMatches[2];
            }
        }

        return $images;
    }
}
