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

namespace AthosCommerce\Feed\Test\Integration\Observer\Product;

use AthosCommerce\Feed\Api\IndexingEntityRepositoryInterface;
use AthosCommerce\Feed\Helper\Constants;
use AthosCommerce\Feed\Model\IndexingEntity;
use AthosCommerce\Feed\Model\ResourceModel\IndexingEntity as IndexingEntityResourceModel;
use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Test\Integration\Traits\IndexingEntitiesTrait;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for grouped associated child save through catalog_product_save_after.
 *
 * Verifies that saving an enabled NVI child linked to a visible grouped parent
 * marks the child for UPSERT instead of DELETE.
 *
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\UpdateObserver
 * @covers \AthosCommerce\Feed\Service\Provider\ProductNextActionProvider
 * @covers \AthosCommerce\Feed\Observer\BaseProductObserver
 */
class GroupedChildUpdateObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-grouped-child-update-observer-';

    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->cleanIndexingEntities(self::SITE_ID_PREFIX . '%');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanIndexingEntities(self::SITE_ID_PREFIX . '%');
    }

    /**
     * @dataProvider visibleParentDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_OnGroupedChildSave_UsesUpsertWhenParentIsVisible(
        int $parentVisibility
    ): void {
        [$parentProduct, $childProduct] = $this->createAndSaveGroupedProduct(
            Status::STATUS_ENABLED,
            $parentVisibility
        );
        $parentId = (int)$parentProduct->getId();
        $childId = (int)$childProduct->getId();

        $this->createIndexingEntityForProduct($childId, Actions::NO_ACTION, false, $parentId);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($childProduct);

        $childEntity = $this->getIndexingEntityByTargetIdAndParentId($childId, $parentId);

        $this->assertNotNull($childEntity);
        $this->assertSame(Actions::UPSERT, $childEntity->getNextAction(), 'Child next_action mismatch');
        $this->assertTrue($childEntity->getIsIndexable(), 'Child is_indexable mismatch');
    }

    public function visibleParentDataProvider(): array
    {
        return [
            'parent_visibility_both' => [
                'parentVisibility' => Visibility::VISIBILITY_BOTH,
            ],
            'parent_visibility_in_catalog' => [
                'parentVisibility' => Visibility::VISIBILITY_IN_CATALOG,
            ],
            'parent_visibility_in_search' => [
                'parentVisibility' => Visibility::VISIBILITY_IN_SEARCH,
            ],
        ];
    }

    /**
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_OnSharedGroupedChildSave_UsesUpsertForAllVisibleParents(): void
    {
        [$parentProducts, $childProduct] = $this->createAndSaveSharedGroupedProduct();
        $childId = (int)$childProduct->getId();

        foreach ($parentProducts as $parentProduct) {
            $this->createIndexingEntityForProduct(
                $childId,
                Actions::NO_ACTION,
                false,
                (int)$parentProduct->getId()
            );
        }

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($childProduct);

        foreach ($parentProducts as $parentProduct) {
            $childEntity = $this->getIndexingEntityByTargetIdAndParentId($childId, (int)$parentProduct->getId());

            $this->assertNotNull($childEntity);
            $this->assertSame(
                Actions::UPSERT,
                $childEntity->getNextAction(),
                'Shared child next_action mismatch for parent ' . $parentProduct->getSku()
            );
            $this->assertTrue(
                $childEntity->getIsIndexable(),
                'Shared child is_indexable mismatch for parent ' . $parentProduct->getSku()
            );
        }
    }

    /**
     * @return array{0: Product, 1: Product}
     */
    private function createAndSaveGroupedProduct(int $status, int $visibility): array
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        /** @var ProductLinkInterfaceFactory $productLinkFactory */
        $productLinkFactory = $this->objectManager->get(ProductLinkInterfaceFactory::class);

        /** @var Product $childProduct */
        $childProduct = $this->objectManager->create(Product::class);
        $childProduct->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Grouped Child ' . uniqid('', true))
            ->setSku('athos_grouped_child_save_' . uniqid('', true))
            ->setPrice(10)
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
            ->setStockData(['qty' => 10, 'is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()]);
        $childProduct = $productRepository->save($childProduct);

        /** @var Product $parentProduct */
        $parentProduct = $this->objectManager->create(Product::class);
        $parentProduct->setTypeId(Grouped::TYPE_CODE)
            ->setAttributeSetId(4)
            ->setName('Grouped Parent ' . uniqid('', true))
            ->setSku('athos_grouped_parent_save_' . uniqid('', true))
            ->setStatus($status)
            ->setVisibility($visibility)
            ->setStockData(['is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()]);

        /** @var ProductLinkInterface $productLink */
        $productLink = $productLinkFactory->create();
        $productLink->setSku($parentProduct->getSku())
            ->setLinkType('associated')
            ->setLinkedProductSku($childProduct->getSku())
            ->setLinkedProductType($childProduct->getTypeId())
            ->getExtensionAttributes()
            ->setQty(1);

        $parentProduct->setProductLinks([$productLink]);
        $parentProduct = $productRepository->save($parentProduct);

        return [$parentProduct, $childProduct];
    }

    /**
     * @return array{0: Product[], 1: Product}
     */
    private function createAndSaveSharedGroupedProduct(): array
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        /** @var ProductLinkInterfaceFactory $productLinkFactory */
        $productLinkFactory = $this->objectManager->get(ProductLinkInterfaceFactory::class);

        /** @var Product $childProduct */
        $childProduct = $this->objectManager->create(Product::class);
        $childProduct->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Shared Grouped Child ' . uniqid('', true))
            ->setSku('athos_grouped_shared_child_save_' . uniqid('', true))
            ->setPrice(10)
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
            ->setStockData(['qty' => 10, 'is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()]);
        $childProduct = $productRepository->save($childProduct);

        $parentProducts = [];
        foreach (['one', 'two'] as $suffix) {
            /** @var Product $parentProduct */
            $parentProduct = $this->objectManager->create(Product::class);
            $parentProduct->setTypeId(Grouped::TYPE_CODE)
                ->setAttributeSetId(4)
                ->setName('Shared Grouped Parent ' . $suffix . ' ' . uniqid('', true))
                ->setSku('athos_grouped_shared_parent_' . $suffix . '_' . uniqid('', true))
                ->setStatus(Status::STATUS_ENABLED)
                ->setVisibility(Visibility::VISIBILITY_BOTH)
                ->setStockData(['is_in_stock' => 1, 'manage_stock' => 0])
                ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()]);

            /** @var ProductLinkInterface $productLink */
            $productLink = $productLinkFactory->create();
            $productLink->setSku($parentProduct->getSku())
                ->setLinkType('associated')
                ->setLinkedProductSku($childProduct->getSku())
                ->setLinkedProductType($childProduct->getTypeId())
                ->getExtensionAttributes()
                ->setQty(1);

            $parentProduct->setProductLinks([$productLink]);
            $parentProducts[] = $productRepository->save($parentProduct);
        }

        return [$parentProducts, $childProduct];
    }

    private function createIndexingEntityForProduct(
        int $productId,
        string $lastAction,
        bool $isIndexable,
        ?int $targetParentId = null
    ): IndexingEntity {
        /** @var IndexingEntity $entity */
        $entity = $this->objectManager->create(IndexingEntity::class);
        $entity->setTargetEntityType(Constants::PRODUCT_KEY);
        $entity->setTargetId($productId);
        $entity->setTargetParentId($targetParentId);
        $entity->setSiteId(self::SITE_ID_PREFIX . random_int(0, 999999999));
        $entity->setNextAction(Actions::NO_ACTION);
        $entity->setLastAction($lastAction);
        $entity->setIsIndexable($isIndexable);

        /** @var IndexingEntityResourceModel $resourceModel */
        $resourceModel = $this->objectManager->get(IndexingEntityResourceModel::class);
        $resourceModel->save($entity);

        return $entity;
    }

    private function getIndexingEntityByTargetIdAndParentId(int $targetId, ?int $targetParentId): ?IndexingEntity
    {
        /** @var SearchCriteriaBuilderFactory $factory */
        $factory = $this->objectManager->get(SearchCriteriaBuilderFactory::class);
        $searchCriteria = $factory->create()
            ->addFilter(IndexingEntity::TARGET_ID, $targetId)
            ->create();

        /** @var IndexingEntityRepositoryInterface $repository */
        $repository = $this->objectManager->get(IndexingEntityRepositoryInterface::class);
        foreach ($repository->getList($searchCriteria)->getItems() as $item) {
            if ($item->getTargetParentId() === $targetParentId) {
                return $item;
            }
        }

        return null;
    }
}
