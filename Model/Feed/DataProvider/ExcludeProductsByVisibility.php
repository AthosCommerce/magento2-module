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

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProvider\Option\Visibility;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\RelationsProvider;
use AthosCommerce\Feed\Model\Feed\ProductTypeIdInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Framework\EntityManager\MetadataPool;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class ExcludeProductsByVisibility implements DataProviderInterface
{
    /**
     * @var ConfigurableResource
     */
    protected $configurableResource;
    /**
     * @var ParentDataContextManager
     */
    private $parentProductContextManager;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ProductTypeIdInterface
     */
    private $productTypeId;

    /**
     * @var RelationsProvider
     */
    private $relationsProvider;

    /**
     * @param ConfigurableResource $configurableResource
     * @param ParentDataContextManager $parentProductContextManager
     * @param Visibility $visibility
     * @param MetadataPool $metadataPool
     * @param AthosCommerceLogger $logger
     * @param ProductTypeIdInterface $productTypeId
     * @param RelationsProvider $relationsProvider
     */
    public function __construct(
        ConfigurableResource     $configurableResource,
        ParentDataContextManager $parentProductContextManager,
        Visibility               $visibility,
        MetadataPool             $metadataPool,
        AthosCommerceLogger      $logger,
        ProductTypeIdInterface   $productTypeId,
        RelationsProvider        $relationsProvider,
    )
    {
        $this->configurableResource = $configurableResource;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->visibility = $visibility;
        $this->metadataPool = $metadataPool;
        $this->logger = $logger;
        $this->productTypeId = $productTypeId;
        $this->relationsProvider = $relationsProvider;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        foreach ($products as $productIndex => $product) {

            $parentType = $product['parent_type_id'] ?? null;
            $parentVisibility = $product['parent_visibility'] ?? null;
            $childVisibility = $product['visibility'] ?? null;
            $childType = $product['type_id'] ?? null;

            $excludeChild = false;

            if (
                in_array($parentType, ['configurable', 'grouped'], true)
                && $parentVisibility === $this->visibility->getVisibilityTextValue(
                    1)
            ) {
                $excludeChild = true;
            }

            if (
                $childType === 'simple'
                && $childVisibility === $this->visibility->getVisibilityTextValue(
                    1)
            ) {
                $excludeChild = true;
            }

            if ($excludeChild) {
                $this->logger->info('FEED FILTER: excluding product', [
                    'child_id' => $product['entity_id'] ?? null,
                    'child_sku' => $product['child_sku'] ?? null,
                    'child_visibility' => $childVisibility,
                    'parent_type_id' => $parentType,
                    'parent_visibility' => $parentVisibility,
                ]);

                unset($products[$productIndex]);
            }
        }

        return $products;
    }

    /**
     *
     */
    public function reset(): void
    {
        // do nothing
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
