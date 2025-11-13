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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Product;

use Magento\Catalog\Api\Data\ProductInterface;

class ProductStorage
{
    /**
     * @var array
     */
    private $products = [];

    /**
     * @param ProductInterface[] $items
     */
    public function set(array $items): void
    {
        $this->products = $items;
    }

    /**
     * @param int $id
     *
     * @return ProductInterface[]|null
     */
    public function getById(int $id): ?array
    {
        return $this->products[$id] ?? null;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->products;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->products = [];
    }
}
