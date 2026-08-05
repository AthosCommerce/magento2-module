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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Helper\Data as ConfigurableHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;

class VariantPosition implements DataProviderInterface
{
    /**
     * @var ConfigurableHelper
     */
    protected $configurableHelper;

    /**
     * @var ConfigurableType
     */
    protected $configurableType;

    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     * @var AthosCommerceLogger
     */
    protected $logger;

    /**
     * @param ConfigurableHelper $configurableHelper
     * @param ConfigurableType $configurableType
     * @param ParentVariantResolver $parentVariantResolver
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ConfigurableHelper $configurableHelper,
        ConfigurableType $configurableType,
        ParentVariantResolver $parentVariantResolver,
        AthosCommerceLogger $logger
    ) {
        $this->configurableHelper = $configurableHelper;
        $this->configurableType = $configurableType;
        $this->parentVariantResolver = $parentVariantResolver;
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
        if (in_array('__variant_position', $ignoredFields, true)) {
            return $products;
        }

        foreach ($products as &$product) {
            /** @var Product|null $simpleProduct */
            $simpleProduct = $product['product_model'] ?? null;

            if (!$simpleProduct instanceof Product) {
                continue;
            }

            $isStandaloneProduct = (bool)($product[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false);
            if ($isStandaloneProduct) {
                $product['__variant_position'] = 1;
                continue;
            }

            try {
                $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($product, $simpleProduct);

                if (!$parentProduct instanceof Product) {
                    $product['__variant_position'] = null;
                    continue;
                }

                if ($parentProduct->getTypeId() === Constant::CONFIGURABLE_TYPE) {
                    $allowedProducts = $this->configurableType->getUsedProducts($parentProduct);
                    $options = $this->configurableHelper->getOptions($parentProduct, $allowedProducts);
                    $product['__variant_position'] = $this->getPositionFromConfigurableIndex(
                        $options['index'] ?? [],
                        (int)$simpleProduct->getId()
                    );
                    continue;
                }

                if ($parentProduct->getTypeId() === Constant::GROUPED_TYPE) {
                    $children = $this->parentVariantResolver->getChildProducts($parentProduct);
                    $product['__variant_position'] = $this->getPositionFromChildren(
                        $children,
                        (int)$simpleProduct->getId()
                    );
                    continue;
                }

                $product['__variant_position'] = '{}';
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $product['__variant_position'] = '{}';
            }
        }
        unset($product);

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
     * @param array $index
     * @param int $simpleId
     * @return int|null
     */
    private function getPositionFromConfigurableIndex(array $index, int $simpleId): ?int
    {
        if (empty($index)) {
            return null;
        }

        $keys = array_map('intval', array_keys($index));
        $pos = array_search($simpleId, $keys, true);

        if ($pos !== false) {
            return $pos + 1;
        }

        return null;
    }

    /**
     * @param Product[] $children
     * @param int $simpleId
     * @return int|null
     */
    private function getPositionFromChildren(array $children, int $simpleId): ?int
    {
        foreach (array_values($children) as $index => $childProduct) {
            if (!$childProduct instanceof Product) {
                continue;
            }

            if ((int)$childProduct->getId() === $simpleId) {
                return $index + 1;
            }
        }

        return null;
    }
}
