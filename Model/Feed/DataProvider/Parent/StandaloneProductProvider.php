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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\ProductTypeIdInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Fallback provider that marks visible simple/virtual products as standalone
 * when neither ConfigurableDataProvider nor GroupedDataProvider claimed them.
 *
 * This covers the case where a product has no configurable or grouped parent
 * so both parent providers exit early, leaving the standalone flags unset.
 */
class StandaloneProductProvider implements DataProviderInterface
{
    /**
     * @var ProductTypeIdInterface
     */
    private $productTypeId;

    /**
     * @var ParentIdSourceFieldEvaluator
     */
    private $parentIdSourceFieldEvaluator;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @param ProductTypeIdInterface $productTypeId
     * @param ParentIdSourceFieldEvaluator $parentIdSourceFieldEvaluator
     * @param AthosCommerceLogger $logger
     */
    public function __construct(
        ProductTypeIdInterface       $productTypeId,
        ParentIdSourceFieldEvaluator $parentIdSourceFieldEvaluator,
        AthosCommerceLogger          $logger
    )
    {
        $this->productTypeId = $productTypeId;
        $this->parentIdSourceFieldEvaluator = $parentIdSourceFieldEvaluator;
        $this->logger = $logger;
    }

    /**
     * Sets standalone product flags for visible simple/virtual products that
     * were not already processed by ConfigurableDataProvider or GroupedDataProvider.
     *
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $childTypeIds = $this->productTypeId->getChildTypeIdsList();
        $ignoredFields = $feedSpecification->getIgnoreFields();
        $parentIdIdentifier = $feedSpecification->getParentIdSourceFieldName();
        $indexingMode = $feedSpecification->getIndexingMode();

        $marked = 0;
        foreach ($products as &$product) {
            $productModel = $product['product_model'] ?? null;
            if (!$productModel instanceof Product) {
                continue;
            }

            if (!in_array($productModel->getTypeId(), $childTypeIds, true)) {
                continue;
            }

            // Skip products that were already marked as standalone by another provider
            if (array_key_exists(Constant::IS_STANDALONE_PRODUCT_KEY, $product)) {
                continue;
            }

            if (!$this->isVisibleIndividually($productModel)) {
                continue;
            }

            $product[Constant::IS_STANDALONE_PRODUCT_KEY] = true;
            $product[Constant::IS_BELONG_TO_PARENT_KEY] = false;

            if (!in_array(Constant::PARENT_ID, $ignoredFields, true)) {
                $parentIdentifierValue = $this->parentIdSourceFieldEvaluator->execute($productModel, $parentIdIdentifier);
                if ($parentIdentifierValue !== null) {
                    $product[Constant::PARENT_ID] = $parentIdentifierValue;
                }
            }

            if (!in_array(Constant::PARENT_SKU, $ignoredFields, true)) {
                $product[Constant::PARENT_SKU] = $productModel->getSku();
            }

            $marked++;
        }
        unset($product);

        $this->logger->debug(
            '[StandaloneProductProvider] Marked standalone products',
            [
                'count' => $marked,
                'mode' => $indexingMode,
                'totalProducts' => count($products),
            ]
        );

        return $products;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
    }

    /**
     * @param Product $product
     * @return bool
     */
    private function isVisibleIndividually(Product $product): bool
    {
        return (int)$product->getVisibility() !== Visibility::VISIBILITY_NOT_VISIBLE;
    }
}
