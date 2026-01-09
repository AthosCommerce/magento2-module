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
use AthosCommerce\Feed\Api\Data\ProductCountItemInterface;

class ProductCountItem extends DataObject implements ProductCountItemInterface
{
    /**
     * @return string
     */
    public function getSiteId(): string
    {
        return (string)$this->getData('site_id');
    }

    /**
     * @param string $siteId
     * @return ProductCountItemInterface
     */
    public function setSiteId(string $siteId): ProductCountItemInterface
    {
        return $this->setData('site_id', $siteId);
    }

    /**
     * @return int
     */
    public function getTotalProductCount(): int
    {
        return (int)$this->getData('total');
    }

    /**
     * @param int $count
     * @return ProductCountItemInterface
     */
    public function setTotalProductCount(int $count): ProductCountItemInterface
    {
        return $this->setData('total', $count);
    }

    /**
     * @return int
     */
    public function getDeleteProductCount(): int
    {
        return (int)$this->getData('delete');
    }

    /**
     * @param int $count
     * @return ProductCountItemInterface
     */
    public function setDeleteProductCount(int $count): ProductCountItemInterface
    {
        return $this->setData('delete', $count);
    }

    /**
     * @return int
     */
    public function getUpsertProductCount(): int
    {
        return (int)$this->getData('upsert');
    }

    /**
     * @param int $count
     * @return ProductCountItemInterface
     */
    public function setUpsertProductCount(int $count): ProductCountItemInterface
    {
        return $this->setData('upsert', $count);
    }
}
