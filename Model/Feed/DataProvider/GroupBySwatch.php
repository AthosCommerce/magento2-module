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
    protected $productRepository;
    /**
     * @var ConfigurableResource
     */
    protected $configurableResource;
    /**
     * @var AthosCommerceLogger
     */
    protected $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ConfigurableResource $configurableResource
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ConfigurableResource $configurableResource,
        AthosCommerceLogger $logger
    ) {
        $this->productRepository = $productRepository;
        $this->configurableResource = $configurableResource;
        $this->logger = $logger;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    public function getData(
        array $products,
        FeedSpecificationInterface $feedSpecification
    ): array {
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
            } catch (\Exception $e) {
                $this->logger->warning(
                    sprintf(
                        'GroupBySwatch: cannot load parent product %s: %s',
                        $parentId,
                        $e->getMessage()
                    )
                );
                $product[self::GROUP_BY_SWATCH_KEY] = false;
                continue;
            }

            $groupData = $this->determineGroupBySwatch($simpleProduct, $parentProduct, $product);
            $product[self::GROUP_BY_SWATCH_KEY] = $groupData;
        }

        return $products;
    }

    /**
     * @param Product $simple
     * @param Product $parent
     * @param array $row
     *
     * @return bool
     */
    private function determineGroupBySwatch(
        Product $simple,
        Product $parent,
        array $row
    ): bool {
        /** @var ConfigurableType $typeInstance */
        $typeInstance = $parent->getTypeInstance();
        $attributes = $typeInstance->getConfigurableAttributes($parent);

        $swatchAttr = null;
        foreach ($attributes as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            if ($productAttribute && $productAttribute->getSwatchInputType() !== null) {
                $swatchAttr = $productAttribute;
                break;
            }
        }

        if (!$swatchAttr) {
            return false;
        }

        $attrCode = $swatchAttr->getAttributeCode();
        $parentId = (int)$parent->getId();

        $label = $simple->getAttributeText($attrCode)
            ?? $simple->getData($attrCode)
            ?? null;

        $labelNormalized = $label !== ''
            ? (string)$label
            : null;


        if (!isset($this->seenSwatchValues[$parentId])) {
            $this->seenSwatchValues[$parentId] = [];
        }

        if ($labelNormalized !== null && !isset($this->seenSwatchValues[$parentId][$labelNormalized])) {
            $this->seenSwatchValues[$parentId][$labelNormalized] = true;
            $groupable = true;
        } else {
            $groupable = false;
        }

        return $groupable;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->seenSwatchValues = [];
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        $this->seenSwatchValues = [];
    }
}
