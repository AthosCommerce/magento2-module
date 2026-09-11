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

namespace AthosCommerce\Feed\Api;

use AthosCommerce\Feed\Api\Data\ApplicationLogResponseInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use AthosCommerce\Feed\Api\Data\CustomerResultsInterface;

interface GetApplicationLogInterface
{
    /**
     * Get the main extension log.
     *
     * @param bool $compressOutput
     * @param int $lastLines Number of lines from the end; ignored when startLine or endLine is provided.
     * @param int $startLine 1-based start line; 0 means beginning of file.
     * @param int $endLine 1-based end line; 0 means end of file.
     * @param string $keyword Plain string or regex (e.g. /pattern/i) to filter matching lines.
     * @param string $startDate ISO 8601 date/datetime to filter lines on or after.
     * @param string $endDate ISO 8601 date/datetime to filter lines on or before.
     *
     * @return ApplicationLogResponseInterface
     *
     * @throws LocalizedException
     */
    public function getExtensionLog(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): ApplicationLogResponseInterface;
    
    /**
     * Clear the main extension log.
     *
     * @return bool
     *
     * @throws LocalizedException
     */
    public function clearExtensionInfoLog(): bool;

    /**
     * Get the cron log.
     *
     * @param bool $compressOutput
     * @param int $lastLines Number of lines from the end; ignored when startLine or endLine is provided.
     * @param int $startLine 1-based start line; 0 means beginning of file.
     * @param int $endLine 1-based end line; 0 means end of file.
     * @param string $keyword Plain string or regex (e.g. /pattern/i) to filter matching lines.
     * @param string $startDate ISO 8601 date/datetime to filter lines on or after.
     * @param string $endDate ISO 8601 date/datetime to filter lines on or before.
     *
     * @return ApplicationLogResponseInterface
     *
     * @throws LocalizedException
     */
    public function getCronLog(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): ApplicationLogResponseInterface;

    /**
     * Get the extension error log.
     *
     * @param bool $compressOutput
     * @param int $lastLines Number of lines from the end; ignored when startLine or endLine is provided.
     * @param int $startLine 1-based start line; 0 means beginning of file.
     * @param int $endLine 1-based end line; 0 means end of file.
     * @param string $keyword Plain string or regex (e.g. /pattern/i) to filter matching lines.
     * @param string $startDate ISO 8601 date/datetime to filter lines on or after.
     * @param string $endDate ISO 8601 date/datetime to filter lines on or before.
     *
     * @return ApplicationLogResponseInterface
     *
     * @throws LocalizedException
     */
    public function getExtensionErrorLog(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): ApplicationLogResponseInterface;

    /**
     * Clear the extension error log.
     *
     * @return bool
     *
     * @throws LocalizedException
     */
    public function clearExtensionErrorLog(): bool;

    /**
     * Get the extension debug log.
     *
     * @param bool $compressOutput
     * @param int $lastLines Number of lines from the end; ignored when startLine or endLine is provided.
     * @param int $startLine 1-based start line; 0 means beginning of file.
     * @param int $endLine 1-based end line; 0 means end of file.
     * @param string $keyword Plain string or regex (e.g. /pattern/i) to filter matching lines.
     * @param string $startDate ISO 8601 date/datetime to filter lines on or after.
     * @param string $endDate ISO 8601 date/datetime to filter lines on or before.
     *
     * @return ApplicationLogResponseInterface
     *
     * @throws LocalizedException
     */
    public function getExtensionDebugLog(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): ApplicationLogResponseInterface;

    /**
     * Clear the extension debug log.
     *
     * @return bool
     *
     * @throws LocalizedException
     */
    public function clearExtensionDebugLog(): bool;
}
