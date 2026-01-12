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

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\ConfigurableProduct\Helper\Data as ConfigurableHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;

class VariantPosition implements DataProviderInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ConfigurableResource
     */
    protected $configurableResource;

    /**
     * @var ConfigurableHelper
     */
    protected $configurableHelper;

    /**
     * @var ConfigurableType
     */
    protected $configurableType;

    /**
     * @var AthosCommerceLogger
     */
    protected $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ConfigurableResource $configurableResource
     * @param ConfigurableHelper $configurableHelper
     * @param ConfigurableType $configurableType
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ConfigurableResource       $configurableResource,
        ConfigurableHelper         $configurableHelper,
        ConfigurableType           $configurableType,
        AthosCommerceLogger        $logger
    )
    {
        $this->productRepository = $productRepository;
        $this->configurableResource = $configurableResource;
        $this->configurableHelper = $configurableHelper;
        $this->configurableType = $configurableType;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (in_array('__variant_position', $ignoredFields)
        ) {
            return $products;
        }

        foreach ($products as &$product) {
            /** @var Product $simpleProduct */
            $simpleProduct = $product['product_model'] ?? null;

            if (!$simpleProduct) {
                continue;
            }

            $simpleId = (int)$simpleProduct->getId();

            /**
             * Get Parent Configurable Product ID
             */
            $parentIds = $this->configurableResource->getParentIdsByChild($simpleId);

            if (empty($parentIds)) {
                $product['__variant_position'] = null;
                continue;
            }

            $parentId = (int)$parentIds[0];

            /**
             * Load Parent Product
             */
            try {
                $parentProduct = $this->productRepository->getById($parentId);
            } catch (\Exception $e) {
                continue;
            }

            /**
             *  Generate __variant_position
             */
            try {
                $allowedProducts = $this->configurableType->getUsedProducts($parentProduct);
                $options = $this->configurableHelper->getOptions($parentProduct, $allowedProducts);
                $product['__variant_position'] = $this->getPosition($options['index'], $simpleId);
            } catch (\Exception $e) {
                $product['__variant_position'] = '{}';
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

    /**
     * @param $index
     * @param $simpleId
     * @return int|string|void
     */
    private function getPosition($index, $simpleId)
    {
        if (!empty($index)) {
            $keys = array_keys($index);
            $pos = array_search($simpleId, $keys);
            if ($pos !== false) {
                return $pos + 1; // 1-based
            }
        }
    }
}
