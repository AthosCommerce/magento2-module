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

namespace AthosCommerce\Feed\Model\Data;

use AthosCommerce\Feed\Api\Data\ProductOptionsResponseInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class ProductOptionsResponse extends AbstractSimpleObject implements ProductOptionsResponseInterface
{
    private const MESSAGE = '';
    private const OPTIONS = 'feed_options';
    private const CATALOG_OPTIONS = 'catalog_options';

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->_get(self::MESSAGE) ?? null;
    }

    /**
     * @param string $message
     * @return ProductOptionsResponse
     */
    public function setMessage(string $message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @return array|string[]
     */
    public function getOptions(): array
    {
        return $this->_get(self::OPTIONS) ?? [];
    }

    /**
     * @param array $options
     * @return ProductOptionsResponse
     */
    public function setOptions(array $options)
    {
        return $this->setData(self::OPTIONS, $options);
    }

    /**
     * @return array|string[]
     */
    public function getCatalogOptions(): array
    {
        return $this->_get(self::CATALOG_OPTIONS) ?? [];
    }

    /**
     * @param array $eavOptions
     * @return ProductOptionsResponse
     */
    public function setCatalogOptions(array $catalogOptions)
    {
        return $this->setData(self::CATALOG_OPTIONS, $catalogOptions);
    }
}
