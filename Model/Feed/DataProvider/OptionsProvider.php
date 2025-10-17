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

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\ResourceModel\Product\Option\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Option\TitleToFieldNameConverter;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;

class OptionsProvider implements DataProviderInterface
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;
    /**
     * @var OptionCollectionFactory
     */
    private $optionCollectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * OptionsProvider constructor.
     * @param MetadataPool $metadataPool
     * @param OptionCollectionFactory $optionCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        MetadataPool $metadataPool,
        OptionCollectionFactory $optionCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->metadataPool = $metadataPool;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     * @throws Exception
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $productIds = [];
        foreach ($products as $product) {
            if (isset($product['product_model']) && $product['product_model']->getData($linkField)) {
                $productIds[] = (int) $product['product_model']->getData($linkField);
            }
        }

        if (!$productIds) {
            return $products;
        }

        $storeId = (int) $this->storeManager->getStore($feedSpecification->getStoreCode())->getId();
        $options = $this->getOptions($productIds, $storeId);

        foreach ($products as &$product) {
            if (!isset($product['product_model']) || !$product['product_model']->getData($linkField)) {
                continue;
            }

            $productId = (int) $product['product_model']->getData($linkField);
            $productOptions = $options[$productId] ?? null;
            if (!$productOptions) {
                continue;
            }

            $product = array_merge($product, $this->buildProductOptions($productOptions, $feedSpecification));
        }

        return $products;
    }

    /**
     * @param array $options
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    private function buildProductOptions(array $options, FeedSpecificationInterface $feedSpecification) : array
    {
        $result = [];
        $ignoreFields = $feedSpecification->getIgnoreFields();
        foreach($options as $option) {
            // Clean up option title for a field name
            $field = TitleToFieldNameConverter::convert($option->getTitle());
            if (in_array($field, $ignoreFields)) {
                continue;
            }

            $optionValues = $option->getValues();
            if($optionValues) {
                foreach($optionValues as $value) {
                    $result[$field][] = $value->getTitle();
                }
            }
        }

        return $result;
    }

    /**
     * @param array $productIds
     * @param int $storeId
     * @return array
     */
    private function getOptions(array $productIds, int $storeId) : array
    {
        /** @var Collection $optionCollection */
        $optionCollection = $this->optionCollectionFactory->create();
        $optionCollection->addFieldToFilter('product_id', ['in' => $productIds])
            ->addFieldToFilter('type', 'drop_down')
            ->addTitleToResult($storeId)
            ->setOrder('sort_order', 'asc')
            ->setOrder('title', 'asc');

        $optionCollection->addValuesToResult($storeId);
        $result = [];
        foreach ($optionCollection as $item) {
            /** @var $item Option */
            $result[$item->getProductId()][] = $item;
        }

        return $result;
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
}
