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
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Psr\Log\LoggerInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use Magento\Swatches\Model\SwatchAttributesProvider;
use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchRenderer;

class JsonConfigProvider implements DataProviderInterface
{
    /**
     * @var SwatchRenderer
     */
    protected $swatchRenderer;

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
     * @var ConfigurableAttributeData
     */
    protected $configurableAttributeData;

    /**
     * @var ConfigurableType
     */
    protected $configurableType;

    /**
     * @var SwatchHelper
     */
    protected $swatchHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SwatchAttributesProvider
     */
    protected $swatchAttributesProvider;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ConfigurableResource       $configurableResource,
        ConfigurableHelper         $configurableHelper,
        ConfigurableAttributeData  $configurableAttributeData,
        ConfigurableType           $configurableType,
        SwatchHelper               $swatchHelper,
        LoggerInterface            $logger,
        SwatchAttributesProvider   $swatchAttributesProvider,
        SwatchRenderer             $swatchRenderer,

    )
    {
        $this->productRepository = $productRepository;
        $this->configurableResource = $configurableResource;
        $this->configurableHelper = $configurableHelper;
        $this->configurableAttributeData = $configurableAttributeData;
        $this->configurableType = $configurableType;
        $this->swatchHelper = $swatchHelper;
        $this->swatchAttributesProvider = $swatchAttributesProvider;
        $this->logger = $logger;
        $this->swatchRenderer = $swatchRenderer;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        foreach ($products as &$product) {
            /** @var Product $simpleProduct */
            $simpleProduct = $product['product_model'] ?? null;

            if (!$simpleProduct) {
                continue;
            }

            $simpleId = (int)$simpleProduct->getId();

            /**
             * Get Parent Configurable Product ID (NO BLOCKS)
             */
            $parentIds = $this->configurableResource->getParentIdsByChild($simpleId);

            if (empty($parentIds)) {
                $product['json_config'] = null;
                $product['swatch_json_config'] = null;
                continue;
            }

            $parentId = (int)$parentIds[0];
            $product['parent_id'] = $parentId;

            /**
             * Load Parent Product
             */
            try {
                $parentProduct = $this->productRepository->getById($parentId);
            } catch (\Exception $e) {
                continue;
            }

            /**
             *  Generate JSON CONFIG (NO BLOCKS)
             */
            try {
                $allowedProducts = $this->configurableType->getUsedProducts($parentProduct);
                $options = $this->configurableHelper->getOptions($parentProduct, $allowedProducts);
                $attributesData = $this->configurableAttributeData->getAttributesData($parentProduct, $options);

                $jsonConfigArr = [
                    'attributes' => $attributesData['attributes'],
                    'index' => $options['index'] ?? [],
                    'salable' => $options['salable'] ?? [],
                    'productId' => $parentId
                ];

                $selectedOptions = [];
                if (!empty($options['index'][$simpleId])) {
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
                }
                $product['__selected_options'] = json_encode($selectedOptions);
                $product['json_config'] = json_encode($jsonConfigArr);
                $product['__variant_position'] = $this->getPosition($options['index'],$simpleId);

            } catch (\Exception $e) {
                $product['json_config'] = '{}';
            }

            try {
                $swatchRenderer = clone $this->swatchRenderer;
                $swatchRenderer->setProduct($parentProduct);
                $product['swatch_json_config'] = $swatchRenderer->getJsonConfig();

            } catch (\Exception $e) {
                $product['swatch_json_config'] = '{}';
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
