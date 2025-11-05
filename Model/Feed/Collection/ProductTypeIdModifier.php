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

namespace AthosCommerce\Feed\Model\Feed\Collection;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProductType;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProductType;

class ProductTypeIdModifier implements ModifierInterface
{
    /**
     * @param Collection $collection
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return Collection
     */
    public function modify(
        Collection $collection,
        FeedSpecificationInterface $feedSpecification
    ): Collection {
        $collection->addAttributeToFilter(
            'type_id',
            [
                'nin' => $this->getProductTypes(),
            ],
        );

        return $collection;
    }

    /**
     * Return product types
     *
     * @return array
     */
    public function getProductTypes()
    {
        return [
            ConfigurableProductType::TYPE_CODE,
            GroupedProductType::TYPE_CODE,
        ];
    }
}
