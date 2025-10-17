<?php
/**
 * Copyright (C) 2025 AthosCommerce <https://athoscommerce.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AthosCommerce\Feed\Model\Feed;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use AthosCommerce\Feed\Api\AppConfigInterface;

class CollectionConfig implements CollectionConfigInterface
{
    const DEFAULT_PAGE_SIZE = 2000;
    const PAGE_SIZE_CONFIG_PATH = 'product_page_size';
    /**
     * @var AppConfigInterface
     */
    private $appConfig;

    /**
     * CollectionConfig constructor.
     * @param AppConfigInterface $appConfig
     */
    public function __construct(
        AppConfigInterface $appConfig
    ) {
        $this->appConfig = $appConfig;
    }

    /**
     * @return int
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function getPageSize(): int
    {
        $pageSize = $this->appConfig->getValue(self::PAGE_SIZE_CONFIG_PATH);
        return $pageSize ? (int) $pageSize : self::DEFAULT_PAGE_SIZE;
    }
}
