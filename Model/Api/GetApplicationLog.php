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

use AthosCommerce\Feed\Api\Data\ApplicationLogResponseInterface;
use AthosCommerce\Feed\Api\Data\ApplicationLogResponseInterfaceFactory;
use AthosCommerce\Feed\Api\GetApplicationLogInterface;
use AthosCommerce\Feed\Helper\LogInfo;

class GetApplicationLog implements GetApplicationLogInterface
{
    /** @var LogInfo */
    private $helper;

    /** @var ApplicationLogResponseInterfaceFactory */
    private $responseFactory;

    /**
     * Build the service with log helpers.
     *
     * @param LogInfo $helper
     * @param ApplicationLogResponseInterfaceFactory $responseFactory
     */
    public function __construct(
        LogInfo $helper,
        ApplicationLogResponseInterfaceFactory $responseFactory
    ) {
        $this->helper = $helper;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Get the main extension log.
     *
     * @param bool $compressOutput
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return ApplicationLogResponseInterface
     */
    public function getExtensionLog(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): ApplicationLogResponseInterface {
        return $this->createResponse($this->helper->getExtensionLogFile(
            $compressOutput,
            $lastLines,
            $startLine,
            $endLine,
            $keyword,
            $startDate,
            $endDate
        ), $compressOutput);
    }
    
    /**
     * Clear the main extension log.
     *
     * @return bool
     */
    public function clearExtensionInfoLog(): bool
    {
        return $this->helper->deleteExtensionLogFile();
    }

    /**
     * Get the cron log.
     *
     * @param bool $compressOutput
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return ApplicationLogResponseInterface
     */
    public function getCronLog(
        bool   $compressOutput = false,
        int    $lastLines = 100,
        int    $startLine = 0,
        int    $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): ApplicationLogResponseInterface {
        return $this->createResponse($this->helper->getCronLogFile(
            $compressOutput,
            $lastLines,
            $startLine,
            $endLine,
            $keyword,
            $startDate,
            $endDate
        ), $compressOutput);
    }

    /**
     * Get the extension error log.
     *
     * @param bool $compressOutput
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return ApplicationLogResponseInterface
     */
    public function getExtensionErrorLog(
        bool $compressOutput = false,
        int $lastLines = 100,
        int $startLine = 0,
        int $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): ApplicationLogResponseInterface {
        return $this->createResponse($this->helper->getExtensionErrorLogFile(
            $compressOutput,
            $lastLines,
            $startLine,
            $endLine,
            $keyword,
            $startDate,
            $endDate
        ), $compressOutput);
    }

    /**
     * Clear the extension error log.
     *
     * @return bool
     */
    public function clearExtensionErrorLog(): bool
    {
        return $this->helper->deleteExtensionErrorLogFile();
    }

    /**
     * Get the extension debug log.
     *
     * @param bool $compressOutput
     * @param int $lastLines
     * @param int $startLine
     * @param int $endLine
     * @param string $keyword
     * @param string $startDate
     * @param string $endDate
     * @return ApplicationLogResponseInterface
     */
    public function getExtensionDebugLog(
        bool $compressOutput = false,
        int $lastLines = 100,
        int $startLine = 0,
        int $endLine = 0,
        string $keyword = '',
        string $startDate = '',
        string $endDate = ''
    ): ApplicationLogResponseInterface {
        return $this->createResponse($this->helper->getExtensionDebugLogFile(
            $compressOutput,
            $lastLines,
            $startLine,
            $endLine,
            $keyword,
            $startDate,
            $endDate
        ), $compressOutput);
    }

    /**
     * Clear the extension debug log.
     *
     * @return bool
     */
    public function clearExtensionDebugLog(): bool
    {
        return $this->helper->deleteExtensionDebugLogFile();
    }

    /**
     * Build the DTO from raw log content.
     *
     * @param string $content
     * @param bool $compressOutput
     * @return ApplicationLogResponseInterface
     */
    private function createResponse(string $content, bool $compressOutput): ApplicationLogResponseInterface
    {
        /** @var ApplicationLogResponseInterface $response */
        $response = $this->responseFactory->create();
        $response->setCompressed($compressOutput);

        if ($content === '') {
            return $response->setLines([])->setContent(null);
        }

        if ($compressOutput) {
            return $response->setLines([])->setContent($content);
        }

        return $response
            ->setLines(preg_split('/\r\n|\n|\r/', $content) ?: [])
            ->setContent(null);
    }
}
