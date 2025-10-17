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

namespace AthosCommerce\Feed\Model\Feed\DataProvider\Configurable;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\Collection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;

class GetAttributesCollection
{
    /**
     * @var JoinProcessorInterface
     */
    private $joinProcessor;
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * GetAttributesCollection constructor.
     * @param JoinProcessorInterface $joinProcessor
     * @param AttributeCollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        JoinProcessorInterface $joinProcessor,
        AttributeCollectionFactory $attributeCollectionFactory
    ) {
        $this->joinProcessor = $joinProcessor;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * @param Product[] $products
     * @return Collection
     */
    public function execute(array $products) : Collection
    {
        $attributesCollection = $this->attributeCollectionFactory->create();
        foreach ($products as $product) {
            $attributesCollection->setProductFilter($product);
        }

        $this->joinProcessor->process($attributesCollection);
        $attributesCollection->orderByPosition();

        return $attributesCollection;
    }
}
