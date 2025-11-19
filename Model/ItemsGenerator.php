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

namespace AthosCommerce\Feed\Model;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProviderPool;
use AthosCommerce\Feed\Model\Feed\ProductTypeIdInterface;
use AthosCommerce\Feed\Model\Feed\SystemFieldsList;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\EntityManager\MetadataPool;

class ItemsGenerator
{
    /**
     * @var DataProviderPool
     */
    private $dataProviderPool;
    /**
     * @var SystemFieldsList
     */
    private $systemFieldsList;
    /**
     * @var MetadataPool
     */
    private $metadataPool;
    /**
     * @var ParentRelationsContext
     */
    private $parentRelationsContext;
    /**
     * @var ProductTypeIdInterface
     */
    private $productTypeId;

    /**
     * @param DataProviderPool $dataProviderPool
     * @param SystemFieldsList $systemFieldsList
     * @param MetadataPool $metadataPool
     * @param ParentRelationsContext $parentRelationsContext
     * @param ProductTypeIdInterface $productTypeId
     */
    public function __construct(
        DataProviderPool $dataProviderPool,
        SystemFieldsList $systemFieldsList,
        MetadataPool $metadataPool,
        ParentRelationsContext $parentRelationsContext,
        ProductTypeIdInterface $productTypeId
    ) {
        $this->dataProviderPool = $dataProviderPool;
        $this->systemFieldsList = $systemFieldsList;
        $this->metadataPool = $metadataPool;
        $this->parentRelationsContext = $parentRelationsContext;
        $this->productTypeId = $productTypeId;
    }

    /**
     * @param array $items
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     */
    public function generate(
        array $items,
        FeedSpecificationInterface $feedSpecification
    ): array {
        if (empty($items)) {
            return [];
        }
        $childIds = [];
        $result = [];
        $childTypeIds = $this->productTypeId->getChildTypeIdsList();
        foreach ($items as $item) {
            if (in_array($item->getTypeId(), $childTypeIds, true)) {
                $childIds[] = (int)$item->getId();
            }
        }
        if (!empty($childIds)) {
            $this->parentRelationsContext->buildContext($childIds, $feedSpecification);
        }

        $data = [];
        foreach ($items as $index => $item) {
            $data[$index] = [
                'entity_id' => $item->getEntityId(),
                'product_model' => $item,
            ];
        }

        $this->systemFieldsList->add('product_model');
        $dataProviders = $this->dataProviderPool->get($feedSpecification->getIgnoreFields());
        foreach ($dataProviders as $dataProvider) {
            $data = $dataProvider->getData($data, $feedSpecification);
        }

        $systemFields = $this->systemFieldsList->get();
        foreach ($data as &$row) {
            foreach ($systemFields as $field) {
                unset($row[$field]);
            }
        }
        $this->parentRelationsContext->reset();

        return $data;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     */
    public function resetDataProvidersAfterFetchItems(
        FeedSpecificationInterface $feedSpecification
    ): void {
        $dataProviders = $this->getDataProviders($feedSpecification);
        foreach ($dataProviders as $dataProvider) {
            $dataProvider->resetAfterFetchItems();
        }
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     */
    public function resetDataProviders(
        FeedSpecificationInterface $feedSpecification
    ): void {
        $dataProviders = $this->getDataProviders($feedSpecification);
        foreach ($dataProviders as $dataProvider) {
            $dataProvider->reset();
        }
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return DataProviderInterface[]
     */
    private function getDataProviders(
        FeedSpecificationInterface $feedSpecification
    ): array {
        return $this->dataProviderPool->get(
            $feedSpecification->getIgnoreFields()
        );
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLinkField(): string
    {
        return $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
    }
}
