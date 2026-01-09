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

namespace AthosCommerce\Feed\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Group\GroupByAttributeResolverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;

class GroupBySwatch implements DataProviderInterface
{
    private const GROUP_BY_SWATCH_KEY = '__group_by_swatch';
    /**
     * @var array
     */
    private array $seenSwatchValues = [];

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var ConfigurableResource
     */
    private $configurableResource;
    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var GroupByAttributeResolverInterface
     */
    private $groupResolver;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ConfigurableResource $configurableResource
     * @param AthosCommerceLogger $logger
     * @param GroupByAttributeResolverInterface $groupResolver
     */
    public function __construct(
        ProductRepositoryInterface        $productRepository,
        ConfigurableResource              $configurableResource,
        AthosCommerceLogger               $logger,
        GroupByAttributeResolverInterface $groupResolver
    )
    {
        $this->productRepository = $productRepository;
        $this->configurableResource = $configurableResource;
        $this->logger = $logger;
        $this->groupResolver = $groupResolver;
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
        if (in_array(
            static::GROUP_BY_SWATCH_KEY,
            $feedSpecification->getIgnoreFields(),
            true
        )) {
            return $products;
        }

        foreach ($products as &$product) {
            $simpleProduct = $product['product_model'] ?? null;

            if (!$simpleProduct instanceof Product) {
                $product[self::GROUP_BY_SWATCH_KEY] = false;
                continue;
            }

            $simpleId = (int)$simpleProduct->getId();
            $parentIds = $this->configurableResource->getParentIdsByChild($simpleId);

            if (empty($parentIds)) {
                $product[self::GROUP_BY_SWATCH_KEY] = false;
                continue;
            }

            $parentId = (int)$parentIds[0];

            try {
                $parentProduct = $this->productRepository->getById($parentId);
                $product[self::GROUP_BY_SWATCH_KEY] = $this->groupResolver->isGroupable(
                    $simpleProduct,
                    $parentProduct,
                    $product
                );
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf(
                        '[GroupBySwatch] Unable to evaluate swatch grouping for variant %s (parent %s). '
                        . 'Storefront swatch display may be affected if its in use. Reason: %s',
                        $simpleId,
                        $parentId,
                        $e->getMessage()
                    )
                );
                $product[self::GROUP_BY_SWATCH_KEY] = false;
                continue;
            }
        }

        return $products;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->groupResolver->reset();
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        $this->groupResolver->reset();
    }
}
