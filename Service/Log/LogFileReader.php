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

declare(strict_types=1);

namespace AthosCommerce\Feed\Service\Log;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

class LogFileReader
{
    /**
     * Maximum bytes to read per line chunk.
     */
    private const MAX_LINE_READ_BYTES = 1048576;

    /**
     * Chunk size used for reverse reads when retrieving only last lines.
     */
    private const TAIL_READ_CHUNK_BYTES = 16384;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param File $fileDriver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        File $fileDriver,
        AthosCommerceLogger $logger
    ) {
        $this->fileDriver = $fileDriver;
        $this->logger = $logger;
    }

    /**
     * @param string $logFile
     * @param bool $compressOutput
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    public function read(
        string $logFile,
        bool $compressOutput = false,
        int $lastLines = 100,
        int $startLine = 0,
        int $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string {
        try {
            $result = $this->readFilteredLogFile(
                $logFile,
                $lastLines,
                $startLine,
                $endLine,
                $keyword,
                $startDate,
                $endDate
            );

            return $result !== '' && $compressOutput
                ? $this->compressString($result)
                : $result;
        } catch (FileSystemException $e) {
            $this->logger->error('Error fetching log file: ' . $e->getMessage() . ' file ' . $logFile);
            return '';
        }
    }

    /**
     * @param string $logFile
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    private function readFilteredLogFile(
        string $logFile,
        int $lastLines,
        int $startLine,
        int $endLine,
        string $keyword,
        string $startDate,
        string $endDate
    ): string {
        $lastLines = max(1, $lastLines);
        $hasDateFilter = $startDate !== '' || $endDate !== '';
        $hasLineRange = $startLine > 0 || $endLine > 0;

        if (!$hasDateFilter && !$hasLineRange && $keyword === '') {
            return $this->readLastLinesFromEnd($logFile, $lastLines);
        }

        $startTs = 0;
        $endTs = PHP_INT_MAX;
        if ($hasDateFilter) {
            $startTs = $startDate !== '' ? (strtotime($startDate) ?: 0) : 0;
            if ($endDate !== '') {
                $endTs = preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)
                    ? (strtotime($endDate . 'T23:59:59') ?: PHP_INT_MAX)
                    : (strtotime($endDate) ?: PHP_INT_MAX);
            }
        }

        $timestampPattern = '/^\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^\]]*)\]/';
        $matchedLines = [];
        $matchedLineNumber = 0;

        $handle = $this->fileDriver->fileOpen($logFile, 'rb');

        try {
            while (!$this->fileDriver->endOfFile($handle)) {
                $line = $this->fileDriver->fileReadLine($handle, self::MAX_LINE_READ_BYTES, "\n");
                $line = rtrim($line, "\r\n");

                if (
                    $hasDateFilter &&
                    !$this->isLineWithinDateRange($line, $startTs, $endTs, $timestampPattern)
                ) {
                    continue;
                }

                if ($keyword !== '' && strpos($line, $keyword) === false) {
                    continue;
                }

                $matchedLineNumber++;
                if ($hasLineRange) {
                    if ($startLine > 0 && $matchedLineNumber < $startLine) {
                        continue;
                    }
                    if ($endLine > 0 && $matchedLineNumber > $endLine) {
                        break;
                    }
                    $matchedLines[] = $line;
                    continue;
                }

                $matchedLines[] = $line;
                if (count($matchedLines) > $lastLines) {
                    array_shift($matchedLines);
                }
            }
        } finally {
            try {
                $this->fileDriver->fileClose($handle);
            } catch (FileSystemException $e) {
                $this->logger->error('Error closing log file: ' . $e->getMessage());
            }
        }

        return implode("\n", $matchedLines);
    }

    /**
     * @param string $logFile
     * @param int $lastLines
     * @return string
     */
    private function readLastLinesFromEnd(string $logFile, int $lastLines): string
    {
        $lastLines = max(1, $lastLines);
        $handle = $this->fileDriver->fileOpen($logFile, 'rb');

        try {
            $this->fileDriver->fileSeek($handle, 0, SEEK_END);
            $fileSize = $this->fileDriver->fileTell($handle);
            if (!is_int($fileSize) || $fileSize <= 0) {
                return '';
            }

            $buffer = '';
            $position = $fileSize;
            $lineBreakCount = 0;

            while ($position > 0 && $lineBreakCount <= $lastLines) {
                $readSize = min(self::TAIL_READ_CHUNK_BYTES, $position);
                $position -= $readSize;

                $this->fileDriver->fileSeek($handle, $position, SEEK_SET);
                $chunk = $this->fileDriver->fileRead($handle, $readSize);
                if ($chunk === '') {
                    break;
                }

                $buffer = $chunk . $buffer;
                $lineBreakCount += substr_count($chunk, "\n");
            }

            $buffer = rtrim($buffer, "\r\n");
            if ($buffer === '') {
                return '';
            }

            $lines = preg_split('/\r\n|\n|\r/', $buffer) ?: [];
            if (count($lines) > $lastLines) {
                $lines = array_slice($lines, -$lastLines);
            }

            return implode("\n", $lines);
        } finally {
            try {
                $this->fileDriver->fileClose($handle);
            } catch (FileSystemException $e) {
                $this->logger->error('Error closing log file: ' . $e->getMessage());
            }
        }
    }

    /**
     * @param string $line
     * @param int $startTs
     * @param int $endTs
     * @param string $timestampPattern
     * @return bool
     */
    private function isLineWithinDateRange(string $line, int $startTs, int $endTs, string $timestampPattern): bool
    {
        if (!preg_match($timestampPattern, $line, $matches)) {
            return false;
        }

        $lineTs = strtotime($matches[1]);
        return $lineTs !== false && $lineTs >= $startTs && $lineTs <= $endTs;
    }

    /**
     * @param string $content
     * @return string
     */
    private function compressString(string $content): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $compressed = gzdeflate($content, 9);
        if ($compressed === false) {
            $this->logger->warning('Failed to compress log output');
            return '';
        }

        $encoded = base64_encode($compressed);
        return rtrim(strtr($encoded, '+/', '-_'), '=');
    }
}
