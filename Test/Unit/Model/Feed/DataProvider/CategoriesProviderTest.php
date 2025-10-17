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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\CategoriesProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\CollectionBuilder;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\GetCategoriesByProductIds;

class CategoriesProviderTest extends \PHPUnit\Framework\TestCase
{
    private $collectionBuilderMock;

    private $getCategoriesByProductIdsMock;

    private $categoriesProvider;

    public function setUp(): void
    {
        $this->collectionBuilderMock = $this->createMock(CollectionBuilder::class);
        $this->getCategoriesByProductIdsMock = $this->createMock(GetCategoriesByProductIds::class);
        $this->categoriesProvider = new CategoriesProvider(
            $this->collectionBuilderMock,
            $this->getCategoriesByProductIdsMock
        );
    }

    public function testGetData()
    {
        $categoryMock = $this->createMock(Category::class);
        $collectionMock = $this->createMock(Collection::class);
        $products = [
            [
                'entity_id' => 1,
            ],
            [
                'entity_id' => 2
            ]
        ];
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);
        $this->getCategoriesByProductIdsMock->expects($this->once())
            ->method('execute')
            ->with([1, 2])
            ->willReturn([
                1 => [
                    [
                        'category_id' => 1,
                        'path' => 'test\path'
                    ],
                ],
                2 => [
                    [
                        'category_id' => 1,
                        'path' => 'test\path'
                    ],
                ],
            ]);

        $this->collectionBuilderMock->expects($this->once())
            ->method('buildCollection')
            ->with([1, 0], $feedSpecificationMock)
            ->willReturn($collectionMock);
        $collectionMock->expects($this->once())
            ->method('getItems')
            ->willReturn(
                [
                    $categoryMock,
                ]
            );
        $feedSpecificationMock->expects($this->any())
            ->method('getStoreCode')
            ->willReturn('1');
        $categoryMock->expects($this->any())
            ->method('setStoreId')
            ->willReturnSelf();
        $categoryMock->expects($this->any())
            ->method('getEntityId')
            ->willReturn(1);
        $categoryMock->expects($this->any())
            ->method('getPathIds')
            ->willReturn([1, 3]);
        $categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('test');
        $categoryMock->expects($this->any())
            ->method('getIncludeInMenu')
            ->willReturn(false);
        $feedSpecificationMock->expects($this->any())
            ->method('getIncludeUrlHierarchy')
            ->willReturn(false);
        $feedSpecificationMock->expects($this->any())
            ->method('getHierarchySeparator')
            ->willReturn('\\');

        $this->assertSame(
            [
                [
                    'entity_id' => 1,
                    'categories' => ['test'],
                    'category_ids' => [1],
                    'category_hierarchy' => ['test']
                ],
                [
                    'entity_id' => 2,
                    'categories' => ['test'],
                    'category_ids' => [1],
                    'category_hierarchy' => ['test']
                ]
            ],
            $this->categoriesProvider->getData($products, $feedSpecificationMock)
        );
    }
}
