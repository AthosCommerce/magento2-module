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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\CategoriesProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\CollectionBuilder;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\GetCategoriesByProductIds;
use AthosCommerce\Feed\Model\Feed\DataProvider\Context\ParentRelationsContext;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CategoriesProviderTest extends TestCase
{
    /**
     * @var CollectionBuilder|MockObject
     */
    private $collectionBuilderMock;

    /**
     * @var GetCategoriesByProductIds|MockObject
     */
    private $getCategoriesByProductIdsMock;

    /**
     * @var ParentRelationsContext|MockObject
     */
    private $parentRelationsContextMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var CategoriesProvider
     */
    private $categoriesProvider;

    protected function setUp(): void
    {
        $this->collectionBuilderMock = $this->createMock(CollectionBuilder::class);
        $this->getCategoriesByProductIdsMock = $this->createMock(GetCategoriesByProductIds::class);
        $this->parentRelationsContextMock = $this->createMock(ParentRelationsContext::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->categoriesProvider = new CategoriesProvider(
            $this->collectionBuilderMock,
            $this->getCategoriesByProductIdsMock,
            $this->parentRelationsContextMock,
            $this->loggerMock
        );
    }

    public function testGetDataUsesStampedParentMarkers(): void
    {
        $collectionMock = $this->createMock(Collection::class);

        $products = [
            [
                'entity_id' => 5,
                Constant::IS_BELONG_TO_PARENT_KEY => 1,
                Constant::RESOLVED_PARENT_ID_KEY => 7,
                Constant::PARENT_ID => 7,
                Constant::RESOLVED_PARENT_SKU_KEY => 'GP',
                Constant::RESOLVED_PARENT_TYPE_KEY => 'grouped',
            ],
        ];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getStoreCode')->willReturn('1');
        $feedSpecificationMock->method('getIncludeMenuCategories')->willReturn(false);
        $feedSpecificationMock->method('getIncludeUrlHierarchy')->willReturn(true);
        $feedSpecificationMock->method('getHierarchySeparator')->willReturn('>');

        $this->getCategoriesByProductIdsMock->expects($this->once())
            ->method('execute')
            ->with([5, 7])
            ->willReturn([
                7 => [
                    [
                        'category_id' => 2,
                        'path' => '1/2',
                    ],
                    [
                        'category_id' => 16,
                        'path' => '1/2/9/10/16',
                    ],
                ],
            ]);

        $this->collectionBuilderMock->expects($this->once())
            ->method('buildCollection')
            ->with([2, 1, 16, 9, 10], $feedSpecificationMock)
            ->willReturn($collectionMock);

        $collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $this->createCategoryMock(2, [1, 2], 'Default Category', 'http://example.com/default.html'),
                $this->createCategoryMock(9, [1, 2, 9], 'Gear', 'http://example.com/gear.html'),
                $this->createCategoryMock(10, [1, 2, 9, 10], 'Fitness Equipment', 'http://example.com/fitness-equipment.html'),
                $this->createCategoryMock(16, [1, 2, 9, 10, 16], 'Type All', 'http://example.com/type-all.html'),
            ]);

        $result = $this->categoriesProvider->getData($products, $feedSpecificationMock);

        $this->assertSame(
            [
                [
                    'entity_id' => 5,
                    Constant::IS_BELONG_TO_PARENT_KEY => 1,
                    Constant::RESOLVED_PARENT_ID_KEY => 7,
                    Constant::PARENT_ID => 7,
                    Constant::RESOLVED_PARENT_SKU_KEY => 'GP',
                    Constant::RESOLVED_PARENT_TYPE_KEY => 'grouped',
                    'categories' => ['Default Category', 'Type All'],
                    'category_ids' => [2, 16],
                    'category_hierarchy' => ['Default Category', 'Gear>Fitness Equipment>Type All'],
                    'url_hierarchy' => [
                        'Default Category[http://example.com/default.html]',
                        'Gear>Fitness Equipment>Type All[http://example.com/type-all.html]',
                    ],
                    'parent_category_id' => 7,
                    'parent_categories' => ['Default Category', 'Type All'],
                    'parent_category_ids' => [2, 16],
                    'parent_category_hierarchy' => ['Default Category', 'Gear>Fitness Equipment>Type All'],
                    'parent_url_hierarchy' => [
                        'Default Category[http://example.com/default.html]',
                        'Gear>Fitness Equipment>Type All[http://example.com/type-all.html]',
                    ],
                ],
            ],
            $result
        );
    }

    private function createCategoryMock(
        int $entityId,
        array $pathIds,
        string $name,
        string $url
    ): Category {
        $categoryMock = $this->createMock(Category::class);
        $categoryMock->method('setStoreId')->willReturnSelf();
        $categoryMock->method('getEntityId')->willReturn($entityId);
        $categoryMock->method('getPathIds')->willReturn($pathIds);
        $categoryMock->method('getName')->willReturn($name);
        $categoryMock->method('getIncludeInMenu')->willReturn(false);
        $categoryMock->method('getUrl')->willReturn($url);

        return $categoryMock;
    }
}
