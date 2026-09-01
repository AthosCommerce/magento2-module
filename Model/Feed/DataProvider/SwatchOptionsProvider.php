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
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Store\Model\StoreManagerInterface;

class SwatchOptionsProvider implements DataProviderInterface
{
    public const FIELD_KEY = '__swatch_options';
    /**
     * @var SwatchHelper
     */
    protected $swatchHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;
    /**
     * @var DataProvider
     */
    private $provider;

    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var array<int, Product|null>
     */
    private $parentProductCache = [];

    /**
     * @var array<string, array>
     */
    private $swatchOptionsCache = [];

    /**
     * @var array<int, array>
     */
    private $swatchMetadataCache = [];

    /**
     * @var string|null
     */
    private $mediaBaseUrl;

    /**
     * @param DataProvider $provider
     * @param AthosCommerceLogger $logger
     * @param SwatchHelper $swatchHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        DataProvider               $provider,
        AthosCommerceLogger        $logger,
        SwatchHelper               $swatchHelper,
        StoreManagerInterface      $storeManager,
        ParentVariantResolver      $parentVariantResolver,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->swatchHelper = $swatchHelper;
        $this->storeManager = $storeManager;
        $this->parentVariantResolver = $parentVariantResolver;
        $this->productRepository = $productRepository;
    }

    /**
     * Returns __swatch_options JSON for configurable product
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();

        if (empty($feedSpecification->getSwatchOptionFieldsNames()) ||
            in_array('swatchOptionSourceFieldNames', $ignoredFields)
        ) {
            return $products;
        }

        $swatch = [];
        if ($feedSpecification->getSwatchOptionFieldsNames() &&
            !in_array('swatchOptionSourceFieldNames', $ignoredFields)
        ) {
            $swatch = $feedSpecification->getSwatchOptionFieldsNames();
        }
        $this->logger->info("[SwatchOptionsProvider] Started");

        foreach ($products as &$product) {

            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            $sku = (string)$productModel->getSku();

            // Only SIMPLE products get SwatchOptionsProvider
            if ($productModel->getTypeId() !== 'simple') {
                $this->logger->debug('[SwatchOptionsProvider] Skipping non-simple product', [
                    'sku' => $sku
                ]);
                continue;
            }

            $isStandaloneProduct = (bool)($product[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false);
            if ($isStandaloneProduct) {
                $product[self::FIELD_KEY] = [];
                continue;
            }

            $parentProduct = $this->resolveParentProduct($product, $productModel);

            if (!$parentProduct) {
                $product[self::FIELD_KEY] = [];
                continue;
            }

            if ($parentProduct->getTypeId() !== Constant::CONFIGURABLE_TYPE) {
                $product[self::FIELD_KEY] = [];
                continue;
            }
            $product[self::FIELD_KEY] = $this->getSwatchOptions($productModel, $parentProduct, $swatch, $sku);
        }
        $this->logger->info("[SwatchOptionsProvider] Completed");

        return $products;
    }

    /**
     * @param array $product
     * @return Product|null
     */
    private function getParentProductFromRow(array $product): ?Product
    {
        $parentId = $product[Constant::RESOLVED_PARENT_ID_KEY] ?? null;
        if (!is_numeric($parentId)) {
            return null;
        }

        try {
            $parentProduct = $this->productRepository->getById((int)$parentId, false, null, true);
            if ($parentProduct instanceof Product) {
                return $parentProduct;
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->warning(
                '[SwatchOptionsProvider] Resolved parent not found in repository',
                ['parentId' => (int)$parentId, 'error' => $e->getMessage()]
            );
        }

        return null;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->parentProductCache = [];
        $this->swatchOptionsCache = [];
        $this->swatchMetadataCache = [];
        $this->mediaBaseUrl = null;
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        $this->reset();
    }

    /**
     * @param array $product
     * @param Product $productModel
     * @return Product|null
     */
    private function resolveParentProduct(array $product, Product $productModel): ?Product
    {
        $productId = (int)$productModel->getId();
        if (!array_key_exists($productId, $this->parentProductCache)) {
            $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($product, $productModel);

            if (!$parentProduct instanceof Product) {
                $parentProduct = $this->getParentProductFromRow($product);
            }

            $this->parentProductCache[$productId] = $parentProduct instanceof Product ? $parentProduct : null;
        }

        return $this->parentProductCache[$productId];
    }

    /**
     * @param Product $productModel
     * @param Product $parentProduct
     * @param array $swatch
     * @param string $sku
     * @return array
     */
    private function getSwatchOptions(Product $productModel, Product $parentProduct, array $swatch, string $sku): array
    {
        $cacheKey = implode(':', [
            (string)$parentProduct->getId(),
            (string)$productModel->getId(),
            md5((string)json_encode(array_values($swatch))),
        ]);
        if (isset($this->swatchOptionsCache[$cacheKey])) {
            return $this->swatchOptionsCache[$cacheKey];
        }

        $configurableAttributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);
        $swatchOptions = [];

        foreach ($configurableAttributes as $attribute) {
            $attr = $attribute->getProductAttribute();
            if (!$attr) {
                continue;
            }

            $attrCode = $attr->getAttributeCode();
            $attrLabel = $attr->getStoreLabel();
            $defaultValue = $attribute->getProductAttribute()->getDefaultValue();
            $simpleValue = $productModel->getAttributeText($attrCode);
            $optionId = $productModel->getData($attrCode);

            $this->logger->debug(
                '[SwatchOptionsProvider] Processing attribute', [
                'sku' => $sku,
                'attr_code' => $attrCode,
                'simple_value' => $simpleValue,
                'option_id' => $optionId
            ]);

            if (!$simpleValue || !in_array($attrCode, $swatch, true)) {
                continue;
            }

            $entry = [
                'label' => $attrLabel,
                'value' => $simpleValue,
                'default' => $defaultValue
            ];

            if ($optionId) {
                $swatchDetail = $this->getSwatchDetail((int)$optionId);
                if ($swatchDetail !== []) {
                    $entry['id'] = $optionId;
                    $entry['colors'] = isset($swatchDetail['value']) ? [$swatchDetail['value']] : [];
                    $entry['image'] = isset($swatchDetail['thumbnail'])
                        ? $this->getMediaBaseUrl() . 'attribute/swatch/' . $swatchDetail['thumbnail']
                        : null;
                }
            }

            $swatchOptions[$attrCode] = $entry;
        }

        $this->swatchOptionsCache[$cacheKey] = $swatchOptions;

        return $this->swatchOptionsCache[$cacheKey];
    }

    /**
     * @param int $optionId
     * @return array
     */
    private function getSwatchDetail(int $optionId): array
    {
        if (!isset($this->swatchMetadataCache[$optionId])) {
            $swatchInfo = $this->swatchHelper->getSwatchesByOptionsId([$optionId]);
            $this->swatchMetadataCache[$optionId] = $swatchInfo[$optionId] ?? [];
        }

        return $this->swatchMetadataCache[$optionId];
    }

    /**
     * @return string
     */
    private function getMediaBaseUrl(): string
    {
        if ($this->mediaBaseUrl === null) {
            $this->mediaBaseUrl = (string)$this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
        }

        return $this->mediaBaseUrl;
    }
}
