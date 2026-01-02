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
use AthosCommerce\Feed\Api\Data\ProductCountResponseInterface;

class ProductCountResponse extends DataObject implements ProductCountResponseInterface
{
    private const TOTAL = 'totalProductCount';
    private const DELETE = 'deleteProductCount';
    private const UPSERT = 'upsertProductCount';

    /**
     * @return int
     */
    public function getTotalProductCount(): int
    {
        return (int)$this->getData(self::TOTAL);
    }

    /**
     * @param int $count
     * @return ProductCountResponseInterface
     */
    public function setTotalProductCount(int $count): ProductCountResponseInterface
    {
        return $this->setData(self::TOTAL, $count);
    }

    /**
     * @return int
     */
    public function getDeleteProductCount(): int
    {
        return (int)$this->getData(self::DELETE);
    }

    /**
     * @param int $count
     * @return ProductCountResponseInterface
     */
    public function setDeleteProductCount(int $count): ProductCountResponseInterface
    {
        return $this->setData(self::DELETE, $count);
    }

    /**
     * @return int
     */
    public function getUpsertProductCount(): int
    {
        return (int)$this->getData(self::UPSERT);
    }

    /**
     * @param int $count
     * @return ProductCountResponseInterface
     */
    public function setUpsertProductCount(int $count): ProductCountResponseInterface
    {
        return $this->setData(self::UPSERT, $count);
    }
}
