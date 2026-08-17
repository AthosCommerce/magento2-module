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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class LogInfo extends AbstractHelper
{
    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    protected $fileDriver;

    /**
     * @var AthosCommerceLogger
     */
    protected $logger;

    public const LOG = [
        'athoscommerce' => 'athoscommerce_feed.log',
        'athoscommerceError' => 'athoscommerce_feed_error.log',
        'athoscommerceDebug' => 'athoscommerce_feed_debug.log',
        'exception' => 'exception.log',
        'groupCron' => 'magento.cron.athoscommerce_task.log',

        // Delete Log Constants
        'deleteExtensionLogFileInfo' => 'File athoscommerce feed log will be removed from the path',
        'deleteExtensionLogFileRemove' => 'File athoscommerce feed log removed successfully',
        'deleteExtensionLogFileError' => 'File athoscommerce feed log not present at the location',

        'deleteExtensionErrorLogFileInfo' => 'File athoscommerce feed error log will be removed from the path',
        'deleteExtensionErrorLogFileRemove' => 'File athoscommerce feed error log removed successfully',
        'deleteExtensionErrorLogFileError' => 'File athoscommerce feed error log not present at the location',

        'deleteExtensionDebugLogFileInfo' => 'File athoscommerce feed debug log will be removed from the path',
        'deleteExtensionDebugLogFileRemove' => 'File athoscommerce feed debug log removed successfully',
        'deleteExtensionDebugLogFileError' => 'File athoscommerce feed debug log not present at the location',

        // Get Log Constants
        'getExtensionLogFileInfo' => 'File athoscommerce feed log will be retrieved from the path',
        'getExtensionLogFileError' => 'File athoscommerce feed log not present at the location:',

        'getExtensionErrorLogInfo' => 'File athoscommerce feed error log will be retrieved from the path',
        'getExtensionErrorLogFileError' => 'File athoscommerce feed error log not present at the location:',

        'getExtensionDebugLogInfo' => 'File athoscommerce feed debug log will be retrieved from the path',
        'getExtensionDebugLogFileError' => 'File athoscommerce feed debug log not present at the location:',

        'getExceptionLogFileInfo' => 'File exception log will be retrieved from the path',
        'getExceptionLogFileError' => 'File exception log not present at the location',

        'getCronLogFileInfo' => 'File group cron log file will be retrieved from the path',
        'getCronLogFileError' => 'File group cron log file not present at the location',
    ];

    /**
     * Constructor.
     *
     * @param DirectoryList $directoryList
     * @param File $fileDriver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        DirectoryList       $directoryList,
        File                $fileDriver,
        AthosCommerceLogger $logger
    )
    {
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->logger = $logger;
    }

    /**
     * Delete Main Extension Log File
     *
     * @return bool
     */
    public function deleteExtensionLogFile(): bool
    {
        return $this->deleteLogFile(
            self::LOG['athoscommerce'],
            self::LOG['deleteExtensionLogFileInfo'],
            self::LOG['deleteExtensionLogFileRemove'],
            self::LOG['deleteExtensionLogFileError']
        );
    }

    /**
     * Get Main Extension Log File
     *
     * @param bool $compressOutput
     * @return string
     */
    public function getExtensionLogFile(bool $compressOutput = false): string
    {
        return $this->getLogFile(
            self::LOG['athoscommerce'],
            self::LOG['getExtensionLogFileInfo'],
            self::LOG['getExtensionLogFileError'],
            $compressOutput
        );
    }

    /**
     * Delete Extension Error Log File
     *
     * @return bool
     */
    public function deleteExtensionErrorLogFile(): bool
    {
        return $this->deleteLogFile(
            self::LOG['athoscommerceError'],
            self::LOG['deleteExtensionErrorLogFileInfo'],
            self::LOG['deleteExtensionErrorLogFileRemove'],
            self::LOG['deleteExtensionErrorLogFileError']
        );
    }

    /**
     * Get Extension Error Log File
     *
     * @param bool $compressOutput
     * @return string
     */
    public function getExtensionErrorLogFile(bool $compressOutput = false): string
    {
        return $this->getLogFile(
            self::LOG['athoscommerceError'],
            self::LOG['getExtensionErrorLogInfo'],
            self::LOG['getExtensionErrorLogFileError'],
            $compressOutput
        );
    }

    /**
     * Delete Extension Debug Log File
     *
     * @return bool
     */
    public function deleteExtensionDebugLogFile(): bool
    {
        return $this->deleteLogFile(
            self::LOG['athoscommerceDebug'],
            self::LOG['deleteExtensionDebugLogFileInfo'],
            self::LOG['deleteExtensionDebugLogFileRemove'],
            self::LOG['deleteExtensionDebugLogFileError']
        );
    }

    /**
     * Get Extension Debug Log File
     *
     * @param bool $compressOutput
     * @return string
     */
    public function getExtensionDebugLogFile(bool $compressOutput = false): string
    {
        return $this->getLogFile(
            self::LOG['athoscommerceDebug'],
            self::LOG['getExtensionDebugLogInfo'],
            self::LOG['getExtensionDebugLogFileError'],
            $compressOutput
        );
    }

    /**
     * Get Exception Log File
     *
     * @param bool $compressOutput
     * @return string
     */
    public function getExceptionLogFile(bool $compressOutput = false): string
    {
        return $this->getLogFile(
            self::LOG['exception'],
            self::LOG['getExceptionLogFileInfo'],
            self::LOG['getExceptionLogFileError'],
            $compressOutput
        );
    }

    /**
     * @param bool $compressOutput
     * @return string
     */
    public function getCronLogFile(bool $compressOutput = false): string
    {
        return $this->getLogFile(
            self::LOG['groupCron'],
            self::LOG['getCronLogFileInfo'],
            self::LOG['getCronLogFileError'],
            $compressOutput
        );
    }

    /**
     * Generic helper method to delete log files safely.
     *
     * @param string $fileName
     * @param string $infoMsg
     * @param string $successMsg
     * @param string $errorMsg
     * @return bool
     */
    private function deleteLogFile(string $fileName, string $infoMsg, string $successMsg, string $errorMsg): bool
    {
        try {
            $logPath = $this->directoryList->getPath(DirectoryList::LOG);
            $logFile = $logPath . '/' . $fileName;

            if ($this->fileDriver->isExists($logFile)) {
                $this->logger->info($infoMsg . ' ' . $logPath);
                $this->fileDriver->deleteFile($logFile);
                $this->logger->info($successMsg . ' ' . $logFile);
                return true;
            }

            $this->logger->error($errorMsg . ' ' . $logFile);
        } catch (FileSystemException $e) {
            $this->logger->error('Error deleting log file: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Generic helper method to retrieve log file content.
     *
     * @param string $fileName
     * @param string $infoMsg
     * @param string $errorMsg
     * @param bool $compressOutput
     * @return string
     */
    private function getLogFile(
        string $fileName,
        string $infoMsg,
        string $errorMsg,
        bool   $compressOutput = false
    ): string
    {
        try {
            $logPath = $this->directoryList->getPath(DirectoryList::LOG);
            $logFile = $logPath . '/' . $fileName;

            if ($this->fileDriver->isExists($logFile)) {
                $this->logger->info($infoMsg . ' ' . $logPath);
                $result = $this->fileDriver->fileGetContents($logFile);

                if ($result !== '' && $compressOutput) {
                    $result = $this->compressString($result);
                }

                return $result;
            }

            $this->logger->error($errorMsg . ' ' . $logPath);
        } catch (FileSystemException $e) {
            $this->logger->error('Error fetching log file: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Compresses string using gzdeflate and base64 encoding.
     *
     * @param string $content
     * @return string
     */
    private function compressString(string $content): string
    {
        $compressed = gzdeflate($content, 9);
        if ($compressed === false) {
            return '';
        }

        $encoded = base64_encode($compressed);
        return rtrim(strtr($encoded, '+/', '-_'), '=');
    }
}
