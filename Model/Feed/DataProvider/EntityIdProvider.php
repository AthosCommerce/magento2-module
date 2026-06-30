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

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ItemIdProvider;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;

class EntityIdProvider implements DataProviderInterface
{
    /**
     * @var ItemIdProvider
     */
    private $itemIdProvider;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ItemIdProvider $itemIdProvider
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ItemIdProvider      $itemIdProvider,
        AthosCommerceLogger $logger
    )
    {
        $this->itemIdProvider = $itemIdProvider;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        foreach ($products as &$product) {
            /** @var Product|null $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            if (!in_array($productModel->getTypeId(), ['simple', 'virtual'], true)) {
                continue;
            }
            
            $product['entity_id'] = $this->itemIdProvider->execute($product, $productModel);

        }
        return $products;
    }

    public function reset(): void
    {
        // TODO: Implement reset() method.
    }

    public function resetAfterFetchItems(): void
    {
        // TODO: Implement resetAfterFetchItems() method.
    }
}
