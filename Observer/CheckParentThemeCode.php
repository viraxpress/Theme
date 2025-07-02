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
namespace ViraXpress\Theme\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Theme\Model\ThemeFactory;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CheckParentThemeCode implements ObserverInterface
{

    /**
     * frontend theme code
     */
    public const THEME_CODE = 'ViraXpress/frontend';

    /**
     * @var ThemeFactory
     */
    protected $themeFactory;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var ThemeProviderInterface
     */
    protected $themeProvider;

    /**
     * @var DesignInterface
     */
    protected $design;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ThemeFactory $themeFactory
     * @param ThemeProviderInterface $themeProvider
     * @param File $file
     * @param DesignInterface $design
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ThemeFactory $themeFactory,
        ThemeProviderInterface $themeProvider,
        File $file,
        DesignInterface $design,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->themeFactory = $themeFactory;
        $this->file = $file;
        $this->themeProvider = $themeProvider;
        $this->design = $design;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Executes the observer logic to copy and move files based on specific requirements.
     *
     * @param \Magento\Framework\Event\Observer $observer The observer object containing event data.
     * @throws \Exception Throws an exception if there is an error during the file operations.
     */
    public function execute(Observer $observer)
    {
        $globalThemeId = $this->design->getConfigurationDesignTheme('frontend', ['website' => null]);
        $globalTheme = $this->themeProvider->getThemeById($globalThemeId);
        $globalThemeCode = $globalTheme->getCode();
        $storeId = $observer->getEvent()->getStore();

        $websites = $this->storeManager->getWebsites();
        foreach ($websites as $website) {
            $websiteId = $website->getId();
            $websiteCode = $website->getCode();
            $websiteName = $website->getName();
            $websiteThemeId = $this->scopeConfig->getValue(
                'design/theme/theme_id',
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            );
            $websiteTheme = $this->themeProvider->getThemeById($websiteThemeId);
            if ($websiteTheme['code'] == self::THEME_CODE) {
                $sourceDir = BP . '/vendor/viraxpress/frontend/vx/vx_frontend/web';
                $destinationDir = BP . "/pub/vx/{$websiteTheme['code']}/web";
                $this->copyAndModifyFiles($sourceDir, $destinationDir, $websiteTheme['code']);
            } else {
                $websiteParentTheme = $this->themeProvider->getThemeById($websiteTheme['theme_id']);
                $websiteParentThemeId = $websiteParentTheme->getParentId();
                $sourceDir = BP . '/vendor/viraxpress/frontend/vx/vx_frontend/web';
                $destinationDir = BP . "/pub/vx/{$websiteTheme['code']}/web";
                if ($websiteParentThemeId) {
                    $websiteParentTheme = $this->themeFactory->create()->load($websiteParentThemeId);
                    if ($websiteParentTheme->getThemePath() === self::THEME_CODE) {
                        $this->copyAndModifyFiles($sourceDir, $destinationDir, $websiteTheme['code']);
                    }
                }
            }
        }
        if ($globalThemeCode === self::THEME_CODE) {
            $sourceDir = BP . '/vendor/viraxpress/frontend/vx/vx_frontend/web';
            $destinationDir = BP . "/pub/vx/{$globalThemeCode}/web";
            $this->copyAndModifyFiles($sourceDir, $destinationDir, $globalThemeCode);
        }
        if ($storeId) {
            $themeId = $this->design->getConfigurationDesignTheme('frontend', ['store' => $storeId]);
            $theme = $this->themeProvider->getThemeById($themeId);
            $parentThemeId = $theme->getParentId();
            $themeCode = $theme->getCode();

            $sourceDir = BP . '/vendor/viraxpress/frontend/vx/vx_frontend/web';
            $destinationDir = BP . "/pub/vx/{$themeCode}/web";
            $scriptFile = $destinationDir . '/tailwind/run_script.sh';
            if ($parentThemeId) {
                $parentTheme = $this->themeFactory->create()->load($parentThemeId);
                if ($parentTheme->getThemePath() === self::THEME_CODE) {
                    $this->copyAndModifyFiles($sourceDir, $destinationDir, $themeCode);
                }
            }

            if (self::THEME_CODE == $themeCode) {
                $this->copyAndModifyFiles($sourceDir, $destinationDir, $themeCode);
            }
        }
    }

    /**
     * Copies files from the source directory to the destination directory and modifies them based on the theme code.
     *
     * @param string $source The path to the source directory from which files will be copied.
     * @param string $destination The path to the destination directory where files will be copied to.
     * @param string $themeCode The theme code used to modify file contents or paths as part of the copy operation.
     */
    protected function copyAndModifyFiles($source, $destination, $themeCode)
    {
        $source = rtrim($source, '/') . '/';
        $destination = rtrim($destination, '/') . '/';

        $dir = opendir($source);
        if ($dir === false) {
            throw new \Exception("Unable to open source directory: " . $source);
        }

        while (($entry = readdir($dir)) !== false) {
            if ($entry != '.' && $entry != '..') {
                $sourcePath = $source . $entry;
                $destinationPath = $destination . $entry;

                if (is_dir($sourcePath)) {
                    if (!$this->file->fileExists($destinationPath)) {
                        $this->file->mkdir($destinationPath, 0775, true);
                    }
                    $this->copyAndModifyFiles($sourcePath, $destinationPath, $themeCode);
                } else {
                    if (!$this->file->fileExists($destinationPath)) {
                        // Check if the current file is run_script.sh
                        if ($entry === 'run_script.sh' && strpos($sourcePath, 'tailwind') !== false) {
                            // Modify the file content
                            $content = $this->file->read($sourcePath);
                            $oldPath = '/var/www/html/pub/media/vx/vx_frontend/web/tailwind';
                            $newPath = BP . '/pub/vx/' . $themeCode . '/web/tailwind';
                            $modifiedContent = str_replace($oldPath, $newPath, $content);

                            // Write the modified content to the destination path
                            $this->file->write($destinationPath, $modifiedContent);
                        } else {
                            // Copy the file to the destination
                            $this->file->cp($sourcePath, $destinationPath);
                        }
                    }
                }
            }
        }
        closedir($dir);
    }
}
