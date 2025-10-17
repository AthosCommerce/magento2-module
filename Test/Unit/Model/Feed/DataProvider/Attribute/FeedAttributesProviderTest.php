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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Attribute;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use AthosCommerce\Feed\Api\Data\TaskSearchResultsInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\FeedAttributesProvider;
use AthosCommerce\Feed\Model\Feed\Specification\Feed;

class FeedAttributesProviderTest extends \PHPUnit\Framework\TestCase
{
    private $productAttributeRepositoryMock;
    private $searchCriteriaBuilderMock;
    private $feedAttributesProvider;

    public function setUp(): void
    {
        $this->productAttributeRepositoryMock = $this->createMock(ProductAttributeRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->feedAttributesProvider = new FeedAttributesProvider(
            $this->productAttributeRepositoryMock,
            $this->searchCriteriaBuilderMock
        );
    }

    public function testGetAttributes()
    {
        $productAttributeInterfaceMock = $this->createMock(ProductAttributeInterface::class);
        $productAttributeInterfaceMockSecond = $this->createMock(ProductAttributeInterface::class);
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $searchResultsMock = $this->createMock(TaskSearchResultsInterface::class);
        $feedSpecificationMock = $this->createMock(Feed::class);

        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn(['test']);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with(ProductAttributeInterface::ATTRIBUTE_CODE, ['test'], 'nin')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $this->productAttributeRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultsMock);
        $searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$productAttributeInterfaceMock, $productAttributeInterfaceMockSecond]);

        $this->assertSame(
            [$productAttributeInterfaceMock, $productAttributeInterfaceMockSecond],
            $this->feedAttributesProvider->getAttributes($feedSpecificationMock)
        );
    }

    public function testGetAttributeCodes()
    {
        $productAttributeInterfaceMock = $this->createMock(ProductAttributeInterface::class);
        $productAttributeInterfaceMockSecond = $this->createMock(ProductAttributeInterface::class);
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $searchResultsMock = $this->createMock(TaskSearchResultsInterface::class);
        $feedSpecificationMock = $this->createMock(Feed::class);

        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn(['test']);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with(ProductAttributeInterface::ATTRIBUTE_CODE, ['test'], 'nin')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $this->productAttributeRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultsMock);
        $searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$productAttributeInterfaceMock, $productAttributeInterfaceMockSecond]);
        $productAttributeInterfaceMock->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('code_1');
        $productAttributeInterfaceMockSecond->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn('code_2');

        $this->assertSame(
            ['code_1', 'code_2'],
            $this->feedAttributesProvider->getAttributeCodes($feedSpecificationMock)
        );
    }
}
