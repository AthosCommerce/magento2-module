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

    public function testGetData(): void
    {
        $categoryMock = $this->createMock(Category::class);
        $collectionMock = $this->createMock(Collection::class);

        $products = [
            ['entity_id' => 1],
            ['entity_id' => 2],
        ];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $feedSpecificationMock->expects($this->once())
            ->method('getStoreCode')
            ->willReturn('1');

        $feedSpecificationMock->method('getIncludeMenuCategories')
            ->willReturn(true);

        $feedSpecificationMock->method('getIncludeUrlHierarchy')
            ->willReturn(false);

        $feedSpecificationMock->method('getHierarchySeparator')
            ->willReturn(' > ');

        $this->getCategoriesByProductIdsMock->expects($this->once())
            ->method('execute')
            ->with([1, 2])
            ->willReturn([
                1 => [
                    [
                        'category_id' => 1,
                        'path' => '1/3',
                    ],
                ],
                2 => [
                    [
                        'category_id' => 1,
                        'path' => '1/3',
                    ],
                ],
            ]);

        $this->collectionBuilderMock->expects($this->once())
            ->method('buildCollection')
            ->with([1, 3], $feedSpecificationMock)
            ->willReturn($collectionMock);

        $collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$categoryMock]);

        $categoryMock->method('setStoreId')->willReturnSelf();
        $categoryMock->method('getEntityId')->willReturn(1);
        $categoryMock->method('getPathIds')->willReturn([1, 3]);
        $categoryMock->method('getName')->willReturn('Category 1');
        $categoryMock->method('getIncludeInMenu')->willReturn(true);

        $result = $this->categoriesProvider->getData($products, $feedSpecificationMock);

        $this->assertSame(
            [
                [
                    'entity_id' => 1,
                    'categories' => ['Category 1'],
                    'category_ids' => [1],
                    'category_hierarchy' => [],
                    'menu_hierarchy' => [],
                ],
                [
                    'entity_id' => 2,
                    'categories' => ['Category 1'],
                    'category_ids' => [1],
                    'category_hierarchy' => [],
                    'menu_hierarchy' => [],
                ],
            ],
            $result
        );
    }
}
