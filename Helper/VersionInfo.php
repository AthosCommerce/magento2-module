<?php
/**
 * Helper to fetch version data.
 *
 * This file is part of AthosCommerce/Feed.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace AthosCommerce\Feed\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\Composer\Package;
use Magento\Framework\Module\Dir;

class VersionInfo extends AbstractHelper
{
    public const MODULE_NAME = 'AthosCommerce_Feed';
    /**
     * @var string
     */
    public const SEARCH_SPRING_FILE_NAME = 'composer.json';
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var Dir
     */
    protected $moduleDirs;

    /** @var ProductMetadataInterface */
    private $productMetadata;

    /**
     * Constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param DirectoryList $directoryList
     * @param Dir $moduleDirs
     */
    public function __construct(ProductMetadataInterface $productMetadata, DirectoryList $directoryList, Dir $moduleDirs,)
    {
        $this->productMetadata = $productMetadata;
        $this->directoryList = $directoryList;
        $this->moduleDirs = $moduleDirs;
    }

    public function getVersion(): array
    {
        $result = [];
        $result[] = [
            'extensionVersion' => $this->getVersionFromComposer(),
            'magento' => $this->productMetadata->getName() . '/' . $this->productMetadata->getVersion() . ' (' . $this->productMetadata->getEdition() . ')',
            'memLimit' => $this->getMemoryLimit(),
            'OSType' => php_uname($mode = "s"),
            'OSVersion' => php_uname($mode = "v"),
            'maxExecutionTime' => ini_get("max_execution_time"),
            'magentoName' => $this->productMetadata->getName(),
            'magentoVersion' => $this->productMetadata->getVersion(),
            'magentoEdition' => $this->productMetadata->getEdition(),
            'magentoRootPath' => $this->directoryList->getRoot(),
            'magentoLogPath' => $this->directoryList->getPath(DirectoryList::LOG)
        ];

        return $result;
    }

    /**
     * Get composer based version info
     * @return false|mixed|string
     */
    public function getVersionFromComposer(): mixed
    {
        try {
            $version = 'unavailable';
            $path = $this->getModuleDirectory(self::MODULE_NAME) . '/' . self::SEARCH_SPRING_FILE_NAME;
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            $composerObj = json_decode(file_get_contents($path));
            //check if composer.json is valid or not
            if (!is_object($composerObj) && !$composerObj instanceof \stdClass) {
                return $version;
            }
            $composerPkg = new Package($composerObj);
            if ($composerPkg->get('version')) {
                return $composerPkg->get('version');
            }
            // through native if above one not loaded, check obj type and array
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            $composerData = json_decode(file_get_contents($path), true);
            if (is_array($composerData) && !empty($composerData['version'])) {
                $version = $composerData['version'];

                return (string)$version;
            }
        } catch (\Exception $e) {
            return $version;
        }

        return $version;
    }

    /**
     * Returns module directory
     *
     * @param string $moduleName
     *
     * @return string
     */
    public function getModuleDirectory($moduleName)
    {
        return $this->moduleDirs->getDir($moduleName);
    }

    public function getMemoryLimit()
    {
        $memoryLimit = trim(strtoupper(ini_get('memory_limit')));

        if (!isset($memoryLimit[0])) {
            $memoryLimit = "128M";
        }

        if (substr($memoryLimit, -1) == 'K') {
            return substr($memoryLimit, 0, -1) * 1024;
        }
        if (substr($memoryLimit, -1) == 'M') {
            return substr($memoryLimit, 0, -1) * 1024 * 1024;
        }
        if (substr($memoryLimit, -1) == 'G') {
            return substr($memoryLimit, 0, -1) * 1024 * 1024 * 1024;
        }
        return $memoryLimit;
    }
}
