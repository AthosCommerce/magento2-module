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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Helper\Data as ConfigurableHelper;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;

class SelectedOptionsProvider implements DataProviderInterface
{
    public const FIELD_KEY_SELECTED_OPTIONS = '__selected_options';

    /**
     * @var array
     */
    private $parentCache = [];

    /**
     * @var ConfigurableHelper
     */
    private $configurableHelper;

    /**
     * @var ConfigurableAttributeData
     */
    private $configurableAttributeData;

    /**
     * @var ConfigurableType
     */
    private $configurableType;

    /**
     * @var AthosCommerceLogger
     */
    private $logger;

    /**
     * @var ParentVariantResolver
     */
    private $parentVariantResolver;

    /**
     *
     * @param ConfigurableHelper $configurableHelper
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param ConfigurableType $configurableType
     * @param AthosCommerceLogger $logger
     * @param ParentVariantResolver $parentVariantResolver
     */
    public function __construct(
        ConfigurableHelper        $configurableHelper,
        ConfigurableAttributeData $configurableAttributeData,
        ConfigurableType          $configurableType,
        AthosCommerceLogger       $logger,
        ParentVariantResolver     $parentVariantResolver
    )
    {
        $this->configurableHelper = $configurableHelper;
        $this->configurableAttributeData = $configurableAttributeData;
        $this->configurableType = $configurableType;
        $this->logger = $logger;
        $this->parentVariantResolver = $parentVariantResolver;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $this->logger->info("[SelectedOptionsProvider] Started");
        $ignoredFields = $feedSpecification->getIgnoreFields();
        if (in_array(self::FIELD_KEY_SELECTED_OPTIONS, $ignoredFields, true)) {
            return $products;
        }

        foreach ($products as &$product) {
            /** @var Product $simpleProduct */
            $simpleProduct = $product['product_model'] ?? null;

            if (!$simpleProduct) {
                continue;
            }

            $simpleId = (int)$simpleProduct->getId();
            $isStandaloneProduct = (bool)($product[Constant::IS_STANDALONE_PRODUCT_KEY] ?? false);
            if ($isStandaloneProduct) {
                $product[self::FIELD_KEY_SELECTED_OPTIONS] = null;
                continue;
            }

            $parentProduct = $this->parentVariantResolver->resolveParentProductForRow($product, $simpleProduct);

            if (!$parentProduct) {
                $product[self::FIELD_KEY_SELECTED_OPTIONS] = null;
                continue;
            }
            $parentId = (int)$parentProduct->getId();

            if ($parentProduct->getTypeId() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $product[self::FIELD_KEY_SELECTED_OPTIONS] = null;
                continue;
            }

            try {

                if (!isset($this->parentCache[$parentId])) {
                    $allowedProducts = $this->configurableType->getUsedProducts($parentProduct);
                    $options = $this->configurableHelper->getOptions($parentProduct, $allowedProducts);
                    $attributesData = $this->configurableAttributeData->getAttributesData($parentProduct, $options);

                    $this->parentCache[$parentId] = [
                        'options' => $options,
                        'attributesData' => $attributesData
                    ];
                }

                $options = $this->parentCache[$parentId]['options'];
                $attributesData = $this->parentCache[$parentId]['attributesData'];

                $selectedOptions = [];
                if (!isset($options['index'][$simpleId]) || !is_array($options['index'][$simpleId])) {
                    $product[self::FIELD_KEY_SELECTED_OPTIONS] = null;
                    continue;
                }

                foreach ($options['index'][$simpleId] as $attributeId => $optionId) {
                    if (isset($attributesData['attributes'][$attributeId])) {
                        $attrCode = $attributesData['attributes'][$attributeId]['code'];
                        foreach ($attributesData['attributes'][$attributeId]['options'] as $option) {
                            if ($option['id'] == $optionId) {
                                $selectedOptions[$attrCode] = ['value' => $option['label']];
                                break;
                            }
                        }
                    }
                }

                $product[self::FIELD_KEY_SELECTED_OPTIONS] = $selectedOptions
                    ? json_encode($selectedOptions)
                    : null;
            } catch (\Throwable $e) {
                $this->logger->error(
                    "[SelectedOptionsProvider] Exception: ",
                    [
                        'method' => __METHOD__,
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]
                );
                $product[self::FIELD_KEY_SELECTED_OPTIONS] = null;
            }
        }
        $this->logger->info("[SelectedOptionsProvider] Completed");
        return $products;
    }


    /**
     * @return void
     */
    public function reset(): void
    {
        $this->parentCache = [];
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
