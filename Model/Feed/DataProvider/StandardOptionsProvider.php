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
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider as ConfigurableDataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Service\Provider\StoreProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Throwable;

class StandardOptionsProvider implements DataProviderInterface
{
    public const FIELD_KEY_STANDARD_OPTIONS = '__standard_options';
    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ConfigurableDataProvider
     */
    private $provider;

    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var StoreProvider
     */
    private $storeProvider;
    /**
     * @var Json
     */
    private $json;

    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var array
     */
    private $optionNames = [];

    /**
     * @param ConfigurableDataProvider $provider
     * @param AthosCommerceLogger $logger
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ConfigurableDataProvider   $provider,
        AthosCommerceLogger        $logger,
        WriterInterface            $configWriter,
        StoreProvider              $storeProvider,
        Json                       $json,
        ParentVariantResolver      $parentVariantResolver,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->storeProvider = $storeProvider;
        $this->json = $json;
        $this->parentVariantResolver = $parentVariantResolver;
        $this->productRepository = $productRepository;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     * @throws Throwable
     */
    public function getData(
        array                      $products,
        FeedSpecificationInterface $feedSpecification
    ): array
    {
        $this->logger->info('[StandardOptionsProvider] Started');
        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            // Only SIMPLE products get __standard_options
            if ($productModel->getTypeId() !== 'simple') {
                continue;
            }

            $isStandaloneProduct = (bool)($product[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false);
            if ($isStandaloneProduct) {
                $product[self::FIELD_KEY_STANDARD_OPTIONS] = [];
                continue;
            }

            $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($product, $productModel);

            if (!$parentProduct) {
                $parentProduct = $this->getParentProductFromRow($product);
            }

            if (!$parentProduct || $parentProduct->getTypeId() !== Constant::CONFIGURABLE_TYPE) {
                $product[self::FIELD_KEY_STANDARD_OPTIONS] = [];
                $this->logger->warning(
                    '[StandardOptions] Parent product missing in context',
                    [
                        'productId' => $productModel->getId(),
                        'row' => $product,
                        'method' => __METHOD__
                    ]
                );
                continue;
            }
            // todo  performance check pending
            if (is_array($parentProduct)) {
                $parentProduct = $parentProduct[0] ?? null;
            }

            if ($parentProduct instanceof \Magento\Catalog\Model\Product) {
                $configurableAttributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);

                $standardOptions = [];

                foreach ($configurableAttributes as $attribute) {
                    $attr = $attribute->getProductAttribute();
                    if (!$attr) {
                        continue;
                    }
                    $attrCode = $attr->getAttributeCode();
                    $attrLabel = $attr->getStoreLabel();
                    // Selected value for this simple product
                    $value = $productModel->getAttributeText($attrCode);
                    if (!$value) {
                        continue;
                    }

                    $standardOptions[$attrCode] = [
                        'label' => $attrLabel,
                        'value' => $value
                    ];
                    $this->optionNames[$attrLabel] = $attrLabel;
                }
                $product[self::FIELD_KEY_STANDARD_OPTIONS] = $standardOptions;
            }

        }

        if (!empty($this->optionNames)) {
            $this->saveOptionNames($feedSpecification);
        }
        $this->logger->info('[StandardOptionsProvider] Completed');
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

        $parentProduct = $this->productRepository->getById((int)$parentId, false, null, true);
        if ($parentProduct instanceof Product) {
            return $parentProduct;
        }

        return null;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @return void
     */
    private function saveOptionNames(FeedSpecificationInterface $feedSpecification)
    {
        try {
            $storeId = $this->storeProvider->getStore($feedSpecification->getStoreCode())->getId();
            $scopeId = $storeId;
            $scope = 'stores';

            if (null == $storeId) {
                $scopeId = 0;
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            }
            $optionNames = $this->json->serialize($this->optionNames);

            $this->configWriter->save(
                \AthosCommerce\Feed\Helper\Constants::XML_PATH_ATTRIBUTE_VARIANT_OPTIONS_LIST,
                $optionNames,
                $scope,
                $scopeId
            );

            $this->logger->debug(
                'GeneratedOptionNames using standardOptions',
                [
                    'optionNamesAsString' => $optionNames,
                    'optionNames' => $this->optionNames,
                ]
            );

        } catch (\Throwable $exception) {
            $this->logger->critical(
                $exception,
                [
                    'trace' => $exception->getTraceAsString(),
                    'storeId' => $storeId,
                    'scope' => $scope,
                    'scopeId' => $scopeId,
                ]
            );
        }
        return;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        //
    }

    /**
     * @return void
     */
    public function resetAfterFetchItems(): void
    {
        //
    }
}
