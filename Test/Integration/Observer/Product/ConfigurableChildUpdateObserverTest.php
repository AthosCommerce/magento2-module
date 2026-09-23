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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for configurable child save through catalog_product_save_after.
 *
 * Verifies that saving an enabled child with visibility "Not Visible Individually"
 * still marks the linked indexing entity for UPSERT when its configurable parent is
 * enabled and visible in the current store.
 *
 * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Observer\Product\UpdateObserver
 * @covers \AthosCommerce\Feed\Service\Provider\ProductNextActionProvider
 * @covers \AthosCommerce\Feed\Observer\BaseProductObserver
 */
class ConfigurableChildUpdateObserverTest extends TestCase
{
    use IndexingEntitiesTrait;

    private const SITE_ID_PREFIX = 'test-configurable-child-update-observer-';

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
    public function testExecute_OnConfigurableChildSave_UsesUpsertWhenParentIsVisible(
        int $parentVisibility
    ): void {
        [$parentProduct, $childProduct] = $this->createAndSaveConfigurableProduct(
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

        $this->assertNotNull(
            $childEntity,
            sprintf('No child IndexingEntity found for child id=%d parent=%d', $childId, $parentId)
        );
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
     * @dataProvider visibleParentDataProvider
     * @magentoConfigFixture current_store athoscommerce/indexing/enable_live_indexing 1
     */
    public function testExecute_OnDisabledConfigurableChildSave_DeletesChildAndUpdatesParent(
        int $parentVisibility
    ): void {
        [$parentProduct, $childProduct] = $this->createAndSaveConfigurableProduct(
            Status::STATUS_ENABLED,
            $parentVisibility
        );
        $parentId = (int)$parentProduct->getId();
        $childId = (int)$childProduct->getId();

        $this->createIndexingEntityForProduct($parentId, Actions::UPSERT, true);
        $this->createIndexingEntityForProduct($childId, Actions::UPSERT, true, $parentId);

        $childProduct->setStatus(Status::STATUS_DISABLED);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($childProduct);

        $parentEntity = $this->getIndexingEntityByTargetIdAndParentId($parentId, null);
        $childEntity = $this->getIndexingEntityByTargetIdAndParentId($childId, $parentId);

        $this->assertNotNull($parentEntity);
        $this->assertNotNull($childEntity);
        $this->assertSame(Actions::UPSERT, $parentEntity->getNextAction(), 'Parent next_action mismatch');
        $this->assertTrue($parentEntity->getIsIndexable(), 'Parent is_indexable mismatch');
        $this->assertSame(Actions::DELETE, $childEntity->getNextAction(), 'Child next_action mismatch');
        $this->assertTrue($childEntity->getIsIndexable(), 'Child is_indexable mismatch');
    }

    /**
     * @return array{0: Product, 1: Product}
     */
    private function createAndSaveConfigurableProduct(int $status, int $visibility): array
    {
        /** @var EavConfig $eavConfig */
        $eavConfig = $this->objectManager->get(EavConfig::class);
        $attribute = $eavConfig->getAttribute(Product::ENTITY, 'test_configurable');

        $options = $attribute->getOptions();
        array_shift($options);

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        $firstOption = reset($options);

        /** @var Product $childProduct */
        $childProduct = $this->objectManager->create(Product::class);
        $childProduct->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Configurable Child ' . uniqid('', true))
            ->setSku('athos_cfg_child_save_' . uniqid('', true))
            ->setPrice(10)
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
            ->setStockData(['qty' => 10, 'is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()])
            ->setData($attribute->getAttributeCode(), $firstOption->getValue());

        $childProduct = $productRepository->save($childProduct);

        /** @var ConfigurableOptionsFactory $optionsFactory */
        $optionsFactory = $this->objectManager->get(ConfigurableOptionsFactory::class);
        $configurableAttributesData = [
            [
                'attribute_id' => $attribute->getId(),
                'code' => $attribute->getAttributeCode(),
                'label' => $attribute->getStoreLabel(),
                'position' => '0',
                'values' => [
                    [
                        'label' => 'test',
                        'attribute_id' => $attribute->getId(),
                        'value_index' => $firstOption->getValue(),
                    ],
                ],
            ],
        ];

        $configurableOptions = $optionsFactory->create($configurableAttributesData);

        /** @var Product $parentProduct */
        $parentProduct = $this->objectManager->create(Product::class);
        $extensionAttributes = $parentProduct->getExtensionAttributes();
        $extensionAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionAttributes->setConfigurableProductLinks([(int)$childProduct->getId()]);
        $parentProduct->setExtensionAttributes($extensionAttributes);

        $parentProduct->setTypeId(Configurable::TYPE_CODE)
            ->setAttributeSetId(4)
            ->setName('Configurable Parent ' . uniqid('', true))
            ->setSku('athos_cfg_parent_save_' . uniqid('', true))
            ->setStatus($status)
            ->setVisibility($visibility)
            ->setStockData(['is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()])
            ->setPrice(10);

        $parentProduct = $productRepository->save($parentProduct);

        return [$parentProduct, $childProduct];
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
