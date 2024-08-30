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
namespace ViraXpress\Theme\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\Filesystem\DirectoryList;

class CreateTailwindFolder implements DataPatchInterface
{
    /**
     * @var File
     */
    protected $file;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ThemeProviderInterface
     */
    protected $themeProvider;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param File $file
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ThemeProviderInterface $themeProvider
     * @param DirectoryList $directoryList
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ThemeProviderInterface $themeProvider,
        DirectoryList $directoryList
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->themeProvider = $themeProvider;
        $this->directoryList = $directoryList;
    }

    /**
     * Applies the patch to copy WYSIWYG files.
     */
    public function apply()
    {
        try {
            $this->copyWysiwygFiles();
        } catch (\Exception $e) {
            $this->logger->error("Error in CreateTailwindFolder patch: " . $e->getMessage());
        }
    }

    /**
     * Moves files from the WYSIWYG source directory to the respective destination path.
     */
    protected function copyWysiwygFiles()
    {
        $sourceDir = BP . '/vendor/viraxpress/frontend/wysiwyg';
        $destinationDir = BP . '/pub/media/wysiwyg';

        $this->copyFilesRecursively($sourceDir, $destinationDir);
    }

    /**
     * Recursively copies files from the source directory to the destination directory.
     *
     * @param string $source The source directory path.
     * @param string $destination The destination directory path.
     * @throws \Exception If the source directory cannot be opened.
     */
    protected function copyFilesRecursively($source, $destination)
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
                    $this->file->mkdir($destinationPath, 0775, true);
                    $this->copyFilesRecursively($sourcePath, $destinationPath);
                } else {
                    $this->file->cp($sourcePath, $destinationPath);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Specifies other patches that this patch depends on.
     *
     * @return array An empty array indicating no dependencies.
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Specifies aliases for this patch.
     *
     * @return array An empty array indicating no aliases.
     */
    public function getAliases()
    {
        return [];
    }
}