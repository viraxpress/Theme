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
     * @param ThemeFactory $themeFactory
     * @param DesignInterface $design
     * @param ThemeProviderInterface $themeProvider
     * @param File $file
     * @param DesignInterface $design
     */
    public function __construct(
        ThemeFactory $themeFactory,
        ThemeProviderInterface $themeProvider,
        File $file,
        DesignInterface $design
    ) {
        $this->themeFactory = $themeFactory;
        $this->file = $file;
        $this->themeProvider = $themeProvider;
        $this->design = $design;
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

        if ($globalThemeCode === self::THEME_CODE) {
            $sourceDir = BP . '/vendor/viraxpress/frontend/vx/vx_frontend/web';
            $destinationDir = BP . "/pub/vx/{$globalThemeCode}/web";
            $this->copyAndModifyFiles($sourceDir, $destinationDir, $globalThemeCode);
        } else {
            $storeId = $observer->getEvent()->getStore();
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
                            $oldPath = BP . '/pub/media/vx/vx_frontend/web/tailwind';
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