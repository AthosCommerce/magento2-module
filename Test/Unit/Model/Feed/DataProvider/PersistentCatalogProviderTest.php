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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\PersistentCatalogProvider;
use Magento\Catalog\Model\Product;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PersistentCatalogProviderTest extends TestCase
{
    /** @var AthosCommerceLogger|MockObject */
    private $loggerMock;

    /** @var ParentVariantResolver|MockObject */
    private $parentVariantResolverMock;

    /** @var StoreManagerInterface|MockObject */
    private $storeManagerMock;

    /** @var PersistentCatalogProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getBaseUrl')
            ->with(UrlInterface::URL_TYPE_MEDIA)
            ->willReturn('https://media.example/');
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);

        $this->provider = new PersistentCatalogProvider(
            $this->loggerMock,
            $this->parentVariantResolverMock,
            $this->storeManagerMock
        );
    }

    public function testGetDataReturnsInputWhenCatalogFieldIsIgnored(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn(['__catalog']);
        $feedSpecificationMock->expects($this->never())
            ->method('getCatalogPreSignedUrl');

        $products = [['entity_id' => 10]];

        $this->assertSame($products, $this->provider->getData($products, $feedSpecificationMock));
    }

    public function testGetDataReturnsInputWhenCatalogPresignedUrlIsMissing(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);
        $feedSpecificationMock->expects($this->once())
            ->method('getCatalogPreSignedUrl')
            ->willReturn('');

        $products = [['entity_id' => 10]];

        $this->assertSame($products, $this->provider->getData($products, $feedSpecificationMock));
    }

    public function testGetDataSkipsRowsWithoutProductModelInstance(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getCatalogPreSignedUrl')->willReturn('https://example.com/catalog.csv');

        $products = [
            ['entity_id' => 1],
            ['entity_id' => 2, 'product_model' => new \stdClass()],
        ];

        $this->parentVariantResolverMock->expects($this->never())->method('resolveParentProductForRow');

        $this->assertSame($products, $this->provider->getData($products, $feedSpecificationMock));
    }

    public function testGetDataBuildsCatalogForStandaloneSimpleProduct(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getCatalogPreSignedUrl')->willReturn('https://example.com/catalog.csv');

        $productMock = $this->createConfiguredMock(Product::class, [
            'getId' => 10,
            'getTypeId' => 'simple',
            'getSku' => 'SKU-10',
            'getName' => 'Simple Product',
            'getPrice' => 19.95,
            'getProductUrl' => 'https://store.example/simple-product',
            'getImage' => '/s/i/simple.jpg',
        ]);
        $productMock->method('getData')
            ->willReturnMap([
                ['thumbnail', '/s/i/simple-thumb.jpg'],
            ]);

        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->with(['product_model' => $productMock], $productMock)
            ->willReturn(null);

        $result = $this->provider->getData(
            [['product_model' => $productMock]],
            $feedSpecificationMock
        );

        $expectedRow = [
            'uid' => '10',
            'sku' => 'SKU-10',
            'parent_uid' => '10',
            'name' => 'Simple Product',
            'price' => 19.95,
            'url' => 'https://store.example/simple-product',
            'imageUrl' => 'https://media.example/catalog/product/s/i/simple.jpg',
            'thumbnailImageUrl' => '',
        ];
        $expectedRow['recordHash'] = hash('sha256',json_encode($expectedRow));

        $this->assertSame($expectedRow, $result[0]['__catalog'][0]);
    }

    public function testGetDataBuildsParentAndVariantRowsWhenProductHasParent(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getCatalogPreSignedUrl')->willReturn('https://example.com/catalog.csv');

        $inputChildMock = $this->createConfiguredMock(Product::class, [
            'getId' => 100,
            'getTypeId' => 'simple',
        ]);

        $parentMock = $this->createConfiguredMock(Product::class, [
            'getId' => 1,
            'getSku' => 'PARENT-1',
            'getName' => 'Parent Product',
            'getPrice' => 99.0,
            'getProductUrl' => 'https://store.example/parent',
            'getImage' => '/p/a/parent.jpg',
        ]);
        $parentMock->method('getData')->willReturnMap([
            ['thumbnail', '/p/a/parent-thumb.jpg'],
        ]);

        $childOneMock = $this->createConfiguredMock(Product::class, [
            'getId' => 2,
            'getSku' => 'CHILD-2',
            'getName' => 'Child 2',
            'getPrice' => 12.0,
            'getProductUrl' => 'https://store.example/child-2',
            'getImage' => '/c/h/child-2.jpg',
        ]);
        $childOneMock->method('getData')->willReturnMap([
            ['thumbnail', '/c/h/child-2-thumb.jpg'],
        ]);

        $childTwoMock = $this->createConfiguredMock(Product::class, [
            'getId' => 3,
            'getSku' => 'CHILD-3',
            'getName' => 'Child 3',
            'getPrice' => 13.0,
            'getProductUrl' => 'https://store.example/child-3',
            'getImage' => '/c/h/child-3.jpg',
        ]);
        $childTwoMock->method('getData')->willReturnMap([
            ['thumbnail', '/c/h/child-3-thumb.jpg'],
        ]);

        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->with(['product_model' => $inputChildMock], $inputChildMock)
            ->willReturn($parentMock);
        $this->parentVariantResolverMock->expects($this->once())
            ->method('getChildProducts')
            ->with($parentMock)
            ->willReturn([$childOneMock, $childTwoMock]);

        $result = $this->provider->getData(
            [['product_model' => $inputChildMock]],
            $feedSpecificationMock
        );

        $this->assertCount(3, $result[0]['__catalog']);
        $this->assertSame('1', $result[0]['__catalog'][0]['uid']);
        $this->assertSame('1_2', $result[0]['__catalog'][1]['uid']);
        $this->assertSame('1_3', $result[0]['__catalog'][2]['uid']);
        $this->assertSame('1', $result[0]['__catalog'][1]['parent_uid']);
        $this->assertSame('1', $result[0]['__catalog'][2]['parent_uid']);
    }

    public function testGetDataReturnsEmptyImageUrlsWhenImageIsNoSelectionOrEmpty(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getCatalogPreSignedUrl')->willReturn('https://example.com/catalog.csv');

        $productMock = $this->createConfiguredMock(Product::class, [
            'getId' => 11,
            'getTypeId' => 'virtual',
            'getSku' => 'VIRTUAL-11',
            'getName' => 'Virtual Product',
            'getPrice' => 10.0,
            'getProductUrl' => 'https://store.example/virtual-11',
            'getImage' => 'no_selection',
        ]);
        $productMock->method('getData')->willReturnMap([
            ['thumbnail', null],
        ]);

        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->with(['product_model' => $productMock], $productMock)
            ->willReturn(null);

        $result = $this->provider->getData(
            [['product_model' => $productMock]],
            $feedSpecificationMock
        );

        $this->assertSame('', $result[0]['__catalog'][0]['imageUrl']);
        $this->assertSame('', $result[0]['__catalog'][0]['thumbnailImageUrl']);
    }

    public function testResetMethodsDoNotThrow(): void
    {
        $this->provider->reset();
        $this->provider->resetAfterFetchItems();

        $this->assertTrue(true);
    }

    public function testGetDataBuildsCatalogOnlyOncePerResolvedParent(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);
        $feedSpecificationMock->method('getCatalogPreSignedUrl')->willReturn('https://example.com/catalog.csv');

        $childOne = $this->createConfiguredMock(Product::class, [
            'getId' => 10,
            'getTypeId' => 'simple',
        ]);
        $childTwo = $this->createConfiguredMock(Product::class, [
            'getId' => 11,
            'getTypeId' => 'simple',
        ]);

        $parentMock = $this->createConfiguredMock(Product::class, [
            'getId' => 1,
            'getSku' => 'PARENT-1',
            'getName' => 'Parent Product',
            'getPrice' => 99.0,
            'getProductUrl' => 'https://store.example/parent',
            'getImage' => '/p/a/parent.jpg',
        ]);
        $parentMock->method('getData')->willReturnMap([
            ['thumbnail', '/p/a/parent-thumb.jpg'],
        ]);

        $variantMock = $this->createConfiguredMock(Product::class, [
            'getId' => 2,
            'getSku' => 'CHILD-2',
            'getName' => 'Child 2',
            'getPrice' => 12.0,
            'getProductUrl' => 'https://store.example/child-2',
            'getImage' => '/c/h/child-2.jpg',
        ]);
        $variantMock->method('getData')->willReturnMap([
            ['thumbnail', '/c/h/child-2-thumb.jpg'],
        ]);

        $this->parentVariantResolverMock->expects($this->exactly(3))
            ->method('resolveParentProductForRow')
            ->willReturnMap([
                [['product_model' => $childOne], $childOne, $parentMock],
                [['product_model' => $childOne], $childOne, $parentMock],
                [['product_model' => $childTwo], $childTwo, $parentMock],
            ]);
        $this->parentVariantResolverMock->expects($this->once())
            ->method('getChildProducts')
            ->with($parentMock)
            ->willReturn([$variantMock]);

        $result = $this->provider->getData(
            [
                ['product_model' => $childOne],
                ['product_model' => $childTwo],
            ],
            $feedSpecificationMock
        );

        $this->assertNotEmpty($result[0]['__catalog']);
        $this->assertSame([], $result[1]['__catalog']);
    }
}

