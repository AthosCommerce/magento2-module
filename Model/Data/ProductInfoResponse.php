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

use Magento\Framework\DataObject;
use AthosCommerce\Feed\Api\Data\ProductInfoResponseInterface;

class ProductInfoResponse extends DataObject implements ProductInfoResponseInterface
{
    private const PRODUCT_IDS = 'product_ids';
    private const PRODUCT_INFO = 'product_info';
    private const MESSAGE = 'message';

    public function getProductIds(): array
    {
        return (array)$this->getData(self::PRODUCT_IDS);
    }

    public function setProductIds(array $productIds)
    {
        return $this->setData(self::PRODUCT_IDS, $productIds);
    }

    public function getProductInfo(): array
    {
        return (array)$this->getData(self::PRODUCT_INFO);
    }

    public function setProductInfo(array $productInfo)
    {
        return $this->setData(self::PRODUCT_INFO, $productInfo);
    }

    public function getMessage(): ?string
    {
        return $this->getData(self::MESSAGE);
    }

    public function setMessage(?string $message)
    {
        return $this->setData(self::MESSAGE, $message);
    }
}
