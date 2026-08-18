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

namespace AthosCommerce\Feed\Model\Api;

use AthosCommerce\Feed\Api\GetApplicationLogInterface;
use AthosCommerce\Feed\Helper\LogInfo;

class GetApplicationLog implements GetApplicationLogInterface
{
    /** @var LogInfo */
    private $helper;

    /**
     * @param LogInfo $helper
     */
    public function __construct(LogInfo $helper)
    {
        $this->helper = $helper;
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
    public function getExceptionLog(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string
    {
        return $this->helper->getExceptionLogFile(
            $compressOutput,
            $lastLines,
            $startLine,
            $endLine,
            $keyword,
            $startDate,
            $endDate
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
    public function getExtensionLog(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string
    {
        return $this->helper->getExtensionLogFile(
            $compressOutput,
            $lastLines,
            $startLine,
            $endLine,
            $keyword,
            $startDate,
            $endDate
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
    public function getCronLog(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''): string
    {
        return $this->helper->getCronLogFile(
            $compressOutput,
            $lastLines,
            $startLine,
            $endLine,
            $keyword,
            $startDate,
            $endDate
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
    public function getExtensionErrorLog(
        bool $compressOutput = false,
        int $lastLines = 100,
        int $startLine = 0,
        int $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string
    {
        return $this->helper->getExtensionErrorLogFile(
            $compressOutput,
            $lastLines,
            $startLine,
            $endLine,
            $keyword,
            $startDate,
            $endDate
        );
    }

    /**
     * @return bool
     */
    public function clearExtensionErrorLog(): bool
    {
        return $this->helper->deleteExtensionErrorLogFile();
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
    public function getExtensionDebugLog(
        bool $compressOutput = false,
        int $lastLines = 100,
        int $startLine = 0,
        int $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): string
    {
        return $this->helper->getExtensionDebugLogFile(
            $compressOutput,
            $lastLines,
            $startLine,
            $endLine,
            $keyword,
            $startDate,
            $endDate
        );
    }

    /**
     * @return bool
     */
    public function clearExtensionDebugLog(): bool
    {
        return $this->helper->deleteExtensionDebugLogFile();
    }
}
