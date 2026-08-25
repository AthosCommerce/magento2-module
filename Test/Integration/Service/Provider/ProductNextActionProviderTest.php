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

namespace AthosCommerce\Feed\Test\Integration\Service\Provider;

use AthosCommerce\Feed\Model\Source\Actions;
use AthosCommerce\Feed\Service\Provider\ProductNextActionProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 * @covers \AthosCommerce\Feed\Service\Provider\ProductNextActionProvider
 */
class ProductNextActionProviderTest extends TestCase
{
    private ?ObjectManagerInterface $objectManager = null;

    private ?ProductNextActionProvider $provider = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->provider = $this->objectManager->get(ProductNextActionProvider::class);
    }

    /**
     * @dataProvider productNextActionDataProvider
     */
    public function testGetNextActionByProduct(
        int $status,
        int $visibility,
        string $expectedNextAction
    ): void {
        $product = $this->createAndSaveTestProduct($status, $visibility);

        $this->assertSame(
            $expectedNextAction,
            $this->provider->getNextActionByProduct($product)
        );
    }

    /**
     * @dataProvider productNextActionDataProvider
     */
    public function testGetNextActionsByProductIds(
        int $status,
        int $visibility,
        string $expectedNextAction
    ): void {
        $product = $this->createAndSaveTestProduct($status, $visibility);
        $productId = (int)$product->getId();

        $nextActions = $this->provider->getNextActionsByProductIds([$productId, $productId, 999999999]);

        $this->assertArrayHasKey($productId, $nextActions);
        $this->assertSame($expectedNextAction, $nextActions[$productId]);
        $this->assertArrayNotHasKey(999999999, $nextActions);
    }

    public function testGetNextActionsByProductIds_WithMixedProducts_ReturnsPerProductActions(): void
    {
        $enabledVisible = $this->createAndSaveTestProduct(Status::STATUS_ENABLED, Visibility::VISIBILITY_BOTH);
        $disabledVisible = $this->createAndSaveTestProduct(Status::STATUS_DISABLED, Visibility::VISIBILITY_BOTH);
        $enabledNotVisible = $this->createAndSaveTestProduct(
            Status::STATUS_ENABLED,
            Visibility::VISIBILITY_NOT_VISIBLE
        );

        $nextActions = $this->provider->getNextActionsByProductIds([
            (int)$enabledVisible->getId(),
            (int)$disabledVisible->getId(),
            (int)$enabledNotVisible->getId(),
        ]);

        $this->assertSame(Actions::UPSERT, $nextActions[(int)$enabledVisible->getId()]);
        $this->assertSame(Actions::DELETE, $nextActions[(int)$disabledVisible->getId()]);
        $this->assertSame(Actions::DELETE, $nextActions[(int)$enabledNotVisible->getId()]);
    }

    public function testGetNextActionsByProductIds_WithEmptyInput_ReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->provider->getNextActionsByProductIds([]));
    }

    public function productNextActionDataProvider(): array
    {
        return [
            'enabled_visible_both' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'expectedNextAction' => Actions::UPSERT,
            ],
            'enabled_visible_in_catalog' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_IN_CATALOG,
                'expectedNextAction' => Actions::UPSERT,
            ],
            'enabled_visible_in_search' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_IN_SEARCH,
                'expectedNextAction' => Actions::UPSERT,
            ],
            'disabled_visible_both' => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_BOTH,
                'expectedNextAction' => Actions::DELETE,
            ],
            'enabled_not_visible' => [
                'status' => Status::STATUS_ENABLED,
                'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
                'expectedNextAction' => Actions::DELETE,
            ],
            'disabled_not_visible' => [
                'status' => Status::STATUS_DISABLED,
                'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
                'expectedNextAction' => Actions::DELETE,
            ],
        ];
    }

    private function createAndSaveTestProduct(int $status, int $visibility): Product
    {
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);

        /** @var Product $product */
        $product = $this->objectManager->create(Product::class);
        $product->setTypeId('simple')
            ->setAttributeSetId(4)
            ->setName('ProductNextActionProvider Test ' . uniqid('', true))
            ->setSku('athos_next_action_' . uniqid('', true))
            ->setPrice(10)
            ->setStatus($status)
            ->setVisibility($visibility)
            ->setStockData(['qty' => 10, 'is_in_stock' => 1, 'manage_stock' => 0])
            ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()]);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->save($product);
    }
}
