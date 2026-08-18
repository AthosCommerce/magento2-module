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
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @return string
     */
    public function getExtensionLogFile(bool $compressOutput = false, int $lastLines = 100, int $startLine = 0, int $endLine = 0, string $keyword = '', string $startDate = '', string $endDate = ''): string
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
     * @return string
     */
    public function getExtensionErrorLogFile(bool $compressOutput = false, int $lastLines = 100, int $startLine = 0, int $endLine = 0, string $keyword = '', string $startDate = '', string $endDate = ''): string
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
     * @return string
     */
    public function getExtensionDebugLogFile(bool $compressOutput = false, int $lastLines = 100, int $startLine = 0, int $endLine = 0, string $keyword = '', string $startDate = '', string $endDate = ''): string
    {
        return $this->getLogFile(
            self::LOG['athoscommerceDebug'],
            self::LOG['getExtensionDebugLogInfo'],
            self::LOG['getExtensionDebugLogFileError'],
            $compressOutput, $lastLines, $startLine, $endLine, $keyword, $startDate, $endDate
        );
    }

    /**
     * Get Exception Log File
     *
     * @param bool $compressOutput
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @return string
     */
    public function getExceptionLogFile(bool $compressOutput = false, int $lastLines = 100, int $startLine = 0, int $endLine = 0, string $keyword = '', string $startDate = '', string $endDate = ''): string
    {
        return $this->getLogFile(
            self::LOG['exception'],
            self::LOG['getExceptionLogFileInfo'],
            self::LOG['getExceptionLogFileError'],
            $compressOutput, $lastLines, $startLine, $endLine, $keyword, $startDate, $endDate
        );
    }

    /**
     * @param bool $compressOutput
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @return string
     */
    public function getCronLogFile(bool $compressOutput = false, int $lastLines = 100, int $startLine = 0, int $endLine = 0, string $keyword = '', string $startDate = '', string $endDate = ''): string
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
     * Filtering order:
     *   1. Date range (startDate / endDate) — applied to timestamped lines only.
     *   2. Keyword — plain substring or regex (e.g. /pattern/i).
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
        try {
            $logPath = $this->directoryList->getPath(DirectoryList::LOG);
            $logFile = $logPath . '/' . $fileName;

            if ($this->fileDriver->isExists($logFile)) {
                $this->logger->info($infoMsg . ' ' . $logPath);
                $result = $this->fileDriver->fileGetContents($logFile);

                if ($result !== '') {
                    $lines = explode("\n", $result);

                    if ($startDate !== '' || $endDate !== '') {
                        $lines = $this->filterByDateRange($lines, $startDate, $endDate);
                    }

                    if ($keyword !== '') {
                        $lines = $this->filterByKeyword($lines, $keyword);
                    }

                    if ($startLine > 0 || $endLine > 0) {
                        $start = $startLine > 0 ? $startLine - 1 : 0;
                        $end   = $endLine > 0 ? $endLine - 1 : count($lines) - 1;
                        $lines = array_slice(array_values($lines), $start, $end - $start + 1);
                    } else {
                        $lines = array_slice(array_values($lines), -$lastLines);
                    }

                    $result = implode("\n", $lines);
                }

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
     * Filters lines by a date range based on the Monolog timestamp [Y-m-d\TH:i:sP].
     * Lines without a recognisable timestamp are excluded when a date filter is active.
     *
     * @param array $lines
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function filterByDateRange(array $lines, string $startDate, string $endDate): array
    {
        $startTs = $startDate !== '' ? (strtotime($startDate) ?: 0) : 0;

        if ($endDate !== '') {
            // If date-only (no time component), include the full day
            $endTs = preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)
                ? (strtotime($endDate . 'T23:59:59') ?: PHP_INT_MAX)
                : (strtotime($endDate) ?: PHP_INT_MAX);
        } else {
            $endTs = PHP_INT_MAX;
        }

        $timestampPattern = '/^\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^\]]*)\]/';

        return array_filter($lines, static function (string $line) use ($startTs, $endTs, $timestampPattern): bool {
            if (preg_match($timestampPattern, $line, $matches)) {
                $lineTs = strtotime($matches[1]);
                return $lineTs !== false && $lineTs >= $startTs && $lineTs <= $endTs;
            }
            return false;
        });
    }

    /**
     * Filters lines by a keyword (plain substring) or regex pattern (e.g. /error/i).
     *
     * @param array $lines
     * @param string $keyword
     * @return array
     */
    private function filterByKeyword(array $lines, string $keyword): array
    {
        $isRegex = preg_match('/^([\/~#@!|]).*\1[gimsxuADJUe]*$/s', $keyword)
            && @preg_match($keyword, '') !== false;

        return array_filter($lines, static function (string $line) use ($keyword, $isRegex): bool {
            return $isRegex
                ? (bool) preg_match($keyword, $line)
                : str_contains($line, $keyword);
        });
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
