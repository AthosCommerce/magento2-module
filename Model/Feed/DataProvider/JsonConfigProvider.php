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

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\LayoutInterface;
use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchesConfigurable;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;

class JsonConfigProvider implements DataProviderInterface
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var Configurable
     */
    private $configurableBlock = null;

    /**
     * @var SwatchesConfigurable
     */
    private $swatchesBlock = null;

    /**
     * @var DataProvider
     */
    private $provider;

    /**
     * @param LayoutInterface $layout
     * @param DataProvider $provider
     */
    public function __construct(
        LayoutInterface $layout,
        DataProvider $provider
    ) {
        $this->layout = $layout;
        $this->provider = $provider;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws LocalizedException
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        if (!$feedSpecification->getIncludeJSONConfig()) {
            return $products;
        }

        $childProducts = $this->provider->getAllChildProducts($products, $feedSpecification);
        $ignoredFields = $feedSpecification->getIgnoreFields();

        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            if (ConfigurableType::TYPE_CODE === $productModel->getTypeId()) {
                if (!in_array('json_config', $ignoredFields)) {
                    $configurableBlock = $this->getConfigurableBlock();
                    $configurableBlock->unsetData();
                    $configurableBlock->setProduct($productModel);
                    if (isset($childProducts[$productModel->getId()])) {
                        $configurableBlock->setAllowProducts($childProducts[$productModel->getId()]);
                    }

                    $product['json_config'] = $configurableBlock->getJsonConfig();
                }

                if (!in_array('swatch_json_config', $ignoredFields)) {
                    $swatchesBlock = $this->getSwatchesBlock();
                    $swatchesBlock->unsetData();
                    $swatchesBlock->setProduct($productModel);
                    if (isset($childProducts[$productModel->getId()])) {
                        $swatchesBlock->setAllowProducts($childProducts[$productModel->getId()]);
                    }

                    $product['swatch_json_config'] = $swatchesBlock->getJsonSwatchConfig();
                }
            }
        }

        return $products;
    }

    /**
     * @return Configurable
     */
    private function getConfigurableBlock() : Configurable
    {
        if (!$this->configurableBlock) {
            $this->configurableBlock = $this->layout->createBlock(Configurable::class);
        }

        return $this->configurableBlock;
    }

    /**
     * @return SwatchesConfigurable
     */
    private function getSwatchesBlock() : SwatchesConfigurable
    {
        if (!$this->swatchesBlock) {
            $this->swatchesBlock = $this->layout->createBlock(SwatchesConfigurable::class);
        }

        return $this->swatchesBlock;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->configurableBlock = null;
        $this->swatchesBlock = null;
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        $this->provider->resetAfterFetchItems();
    }
}
