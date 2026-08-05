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
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentDataContextManager;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Service\Provider\StoreProvider;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Throwable;

class StandardOptionsProvider implements DataProviderInterface
{
    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ConfigurableDataProvider
     */
    private $provider;

    /**
     * @var ParentDataContextManager
     */
    private $parentProductContextManager;
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
     * @var array
     */
    private $optionNames = [];

    /**
     * @param ConfigurableDataProvider $provider
     * @param AthosCommerceLogger $logger
     * @param ParentDataContextManager $parentProductContextManager
     * @param Configurable $configurableType
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ConfigurableDataProvider $provider,
        AthosCommerceLogger      $logger,
        ParentDataContextManager $parentProductContextManager,
        Configurable             $configurableType,
        WriterInterface          $configWriter,
        StoreProvider            $storeProvider,
        Json                     $json
    )
    {
        $this->provider = $provider;
        $this->logger = $logger;
        $this->parentProductContextManager = $parentProductContextManager;
        $this->configurableType = $configurableType;
        $this->configWriter = $configWriter;
        $this->storeProvider = $storeProvider;
        $this->json = $json;
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
        $this->logger->info('[StandardOptionsProvider] started');
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

            $parentIds = $this->configurableType->getParentIdsByChild($productModel->getId());

            if (empty($parentIds)) {
                $product['standard_options'] = [];
                continue;
            }

            $parentId = (int)$parentIds[0];
            $parentProduct = $this->parentProductContextManager->getParentsDataByProductId($parentId);

            if (!$parentProduct) {
                $this->logger->warning(
                    '[StandardOptions] Parent product missing in context',
                    [
                        'productId' => $productModel->getId(),
                        'parentIds' => $parentIds,
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
                $product['__standard_options'] = $standardOptions;
            }

        }

        if (!empty($this->optionNames)) {
            $this->saveOptionNames($feedSpecification);
        }
        $this->logger->info('[StandardOptionsProvider] completed');
        return $products;
    }

    /**
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
     *
     */
    public function reset(): void
    {
        //
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        //
    }
}
