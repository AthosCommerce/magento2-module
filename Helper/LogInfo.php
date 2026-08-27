<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace AthosCommerce\Feed\Helper;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Service\Log\LogFileReader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

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

    /**
     * @var LogFileReader
     */
    private $logFileReader;

    public const LOG = [
        'athoscommerce' => 'athoscommerce_feed.log',
        'athoscommerceError' => 'athoscommerce_feed_error.log',
        'athoscommerceDebug' => 'athoscommerce_feed_debug.log',
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

        'getCronLogFileInfo' => 'File group cron log file will be retrieved from the path',
        'getCronLogFileError' => 'File group cron log file not present at the location',
    ];

    /**
     * Constructor.
     *
     * @param DirectoryList $directoryList
     * @param File $fileDriver
     * @param AthosCommerceLogger $logger
     * @param LogFileReader $logFileReader
     */
    public function __construct(
        DirectoryList       $directoryList,
        File                $fileDriver,
        AthosCommerceLogger $logger,
        LogFileReader $logFileReader
    )
    {
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->logger = $logger;
        $this->logFileReader = $logFileReader;
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
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    public function getExtensionLogFile(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string
    {
        return $this->getLogFile(
            self::LOG['athoscommerce'],
            self::LOG['getExtensionLogFileInfo'],
            self::LOG['getExtensionLogFileError'],
            $compressOutput, $lastLines, $startLine, $endLine, $keyword, $startDate, $endDate
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
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    public function getExtensionErrorLogFile(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string
    {
        return $this->getLogFile(
            self::LOG['athoscommerceError'],
            self::LOG['getExtensionErrorLogInfo'],
            self::LOG['getExtensionErrorLogFileError'],
            $compressOutput, $lastLines, $startLine, $endLine, $keyword, $startDate, $endDate
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
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    public function getExtensionDebugLogFile(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string
    {
        return $this->getLogFile(
            self::LOG['athoscommerceDebug'],
            self::LOG['getExtensionDebugLogInfo'],
            self::LOG['getExtensionDebugLogFileError'],
            $compressOutput, $lastLines, $startLine, $endLine, $keyword, $startDate, $endDate
        );
    }

    /**
     * @param bool $compressOutput
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    public function getCronLogFile(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string
    {
        return $this->getLogFile(
            self::LOG['groupCron'],
            self::LOG['getCronLogFileInfo'],
            self::LOG['getCronLogFileError'],
            $compressOutput, $lastLines, $startLine, $endLine, $keyword, $startDate, $endDate
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
    private function deleteLogFile(
        string $fileName,
        string $infoMsg,
        string $successMsg,
        string $errorMsg
    ): bool
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
     * Filtering order:
     *   1. Date range (startDate / endDate) — applied to timestamped lines only.
     *   2. Keyword — plain substring match.
     *   3. Positional slice — startLine/endLine (1-based, takes priority over lastLines)
     *      or lastLines (last N matching lines, default 100).
     *
     * Log timestamp format: [2025-01-15T10:30:45+05:30]
     *
     * @param string $fileName
     * @param string $infoMsg
     * @param string $errorMsg
     * @param bool $compressOutput
     * @param int $lastLines
     * @param int $startLine 1-based; 0 = beginning of file
     * @param int $endLine 1-based; 0 = end of file
     * @param string $keyword Plain string or /regex/flags
     * @param string $startDate ISO 8601 date/datetime (inclusive)
     * @param string $endDate ISO 8601 date/datetime (inclusive; date-only covers full day)
     * @return string
     */
    private function getLogFile(
        string $fileName,
        string $infoMsg,
        string $errorMsg,
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string
    {
        $logFile = '';
        try {
            $logPath = $this->directoryList->getPath(DirectoryList::LOG);
            $logFile = $logPath . '/' . $fileName;

            if ($this->fileDriver->isExists($logFile)) {
                $this->logger->info($infoMsg . ' ' . $logPath);
                return $this->logFileReader->read(
                    $logFile,
                    $compressOutput,
                    $lastLines,
                    $startLine,
                    $endLine,
                    $keyword,
                    $startDate,
                    $endDate
                );
            }

            $this->logger->debug($errorMsg . ' ' . $logPath);
        } catch (FileSystemException $e) {
            $this->logger->error('Error fetching log file: ' . $e->getMessage() . ' file ' . $logFile);
        }

        return '';
    }
}
