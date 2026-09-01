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
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\Constant;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\VariantPosition;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Helper\Data as ConfigurableHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VariantPositionTest extends TestCase
{
    /**
     * @var ConfigurableHelper|MockObject
     */
    private $configurableHelperMock;

    /**
     * @var ConfigurableType|MockObject
     */
    private $configurableTypeMock;

    /**
     * @var ParentVariantResolver|MockObject
     */
    private $parentVariantResolverMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var VariantPosition
     */
    private $variantPosition;

    protected function setUp(): void
    {
        $this->configurableHelperMock = $this->createMock(ConfigurableHelper::class);
        $this->configurableTypeMock = $this->createMock(ConfigurableType::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->variantPosition = new VariantPosition(
            $this->configurableHelperMock,
            $this->configurableTypeMock,
            $this->parentVariantResolverMock,
            $this->loggerMock
        );
    }

    public function testGetDataReturnsProductsWhenVariantPositionIsIgnored(): void
    {
        $products = [
            ['product_model' => $this->createMock(Product::class)],
        ];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn(['__variant_position']);

        $this->assertSame($products, $this->variantPosition->getData($products, $feedSpecificationMock));
    }

    public function testGetDataSkipsRowsWithoutProductModel(): void
    {
        $products = [
            ['sku' => 'missing-model'],
        ];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertSame($products, $result);
        $this->assertArrayNotHasKey('__variant_position', $result[0]);
    }

    public function testGetDataSetsPositionOneForStandaloneProduct(): void
    {
        $simpleProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 10,
        ]);

        $products = [[
            'product_model' => $simpleProductMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => true,
        ]];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertSame(1, $result[0]['__variant_position']);
    }

    public function testGetDataSetsNullWhenNoParentProductIsResolved(): void
    {
        $simpleProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 10,
        ]);

        $products = [[
            'product_model' => $simpleProductMock,
            Constant::IS_STANDALONE_PRODUCT_KEY => false,
        ]];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $simpleProductMock)
            ->willReturn(null);

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertNull($result[0]['__variant_position']);
    }

    public function testGetDataResolvesPositionForConfigurableParent(): void
    {
        $simpleProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 20,
        ]);

        $parentProductMock = $this->createConfiguredMock(Product::class, [
            'getTypeId' => Constant::CONFIGURABLE_TYPE,
        ]);

        $allowedProducts = [
            $this->createMock(Product::class),
            $this->createMock(Product::class),
        ];

        $products = [[
            'product_model' => $simpleProductMock,
        ]];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->willReturn($parentProductMock);

        $this->configurableTypeMock->expects($this->once())
            ->method('getUsedProducts')
            ->with($parentProductMock)
            ->willReturn($allowedProducts);

        $this->configurableHelperMock->expects($this->once())
            ->method('getOptions')
            ->with($parentProductMock, $allowedProducts)
            ->willReturn([
                'index' => [
                    '11' => [],
                    '20' => [],
                    '35' => [],
                ],
            ]);

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertSame(2, $result[0]['__variant_position']);
    }

    public function testGetDataReturnsNullWhenConfigurableIndexDoesNotContainSimpleProduct(): void
    {
        $simpleProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 999,
        ]);

        $parentProductMock = $this->createConfiguredMock(Product::class, [
            'getTypeId' => Constant::CONFIGURABLE_TYPE,
        ]);

        $products = [[
            'product_model' => $simpleProductMock,
        ]];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentVariantResolverMock->method('resolveParentProductForRow')
            ->willReturn($parentProductMock);

        $this->configurableTypeMock->method('getUsedProducts')
            ->willReturn([]);

        $this->configurableHelperMock->method('getOptions')
            ->willReturn([
                'index' => [
                    '11' => [],
                    '20' => [],
                ],
            ]);

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertNull($result[0]['__variant_position']);
    }

    public function testGetDataResolvesPositionForGroupedParent(): void
    {
        $simpleProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 200,
        ]);

        $parentProductMock = $this->createConfiguredMock(Product::class, [
            'getTypeId' => Constant::GROUPED_TYPE,
        ]);

        $childOne = $this->createConfiguredMock(Product::class, ['getId' => 100]);
        $childTwo = $this->createConfiguredMock(Product::class, ['getId' => 200]);
        $childThree = $this->createConfiguredMock(Product::class, ['getId' => 300]);

        $products = [[
            'product_model' => $simpleProductMock,
        ]];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->willReturn($parentProductMock);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('getChildProducts')
            ->with($parentProductMock)
            ->willReturn([$childOne, $childTwo, $childThree]);

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertSame(2, $result[0]['__variant_position']);
    }

    public function testGetDataReturnsNullWhenGroupedChildrenDoNotContainSimpleProduct(): void
    {
        $simpleProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 500,
        ]);

        $parentProductMock = $this->createConfiguredMock(Product::class, [
            'getTypeId' => Constant::GROUPED_TYPE,
        ]);

        $childOne = $this->createConfiguredMock(Product::class, ['getId' => 100]);
        $childTwo = $this->createConfiguredMock(Product::class, ['getId' => 200]);

        $products = [[
            'product_model' => $simpleProductMock,
        ]];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentVariantResolverMock->method('resolveParentProductForRow')
            ->willReturn($parentProductMock);

        $this->parentVariantResolverMock->method('getChildProducts')
            ->willReturn([$childOne, $childTwo]);

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertNull($result[0]['__variant_position']);
    }

    public function testGetDataReturnsEmptyJsonForUnsupportedParentType(): void
    {
        $simpleProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 20,
        ]);

        $parentProductMock = $this->createConfiguredMock(Product::class, [
            'getTypeId' => 'bundle',
        ]);

        $products = [[
            'product_model' => $simpleProductMock,
        ]];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentVariantResolverMock->method('resolveParentProductForRow')
            ->willReturn($parentProductMock);

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertSame('{}', $result[0]['__variant_position']);
    }

    public function testGetDataReturnsEmptyJsonAndLogsErrorOnException(): void
    {
        $simpleProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 20,
        ]);

        $products = [[
            'product_model' => $simpleProductMock,
        ]];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->willThrowException(new \Exception('Resolver failure'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Resolver failure');

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertSame('{}', $result[0]['__variant_position']);
    }

    public function testResetDoesNothing(): void
    {
        $this->variantPosition->reset();
        $this->addToAssertionCount(1);
    }

    public function testResetAfterFetchItemsDoesNothing(): void
    {
        $this->variantPosition->resetAfterFetchItems();
        $this->addToAssertionCount(1);
    }

    public function testGetDataCachesParentDataForRepeatedRows(): void
    {
        $simpleProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 20,
        ]);

        $parentProductMock = $this->createConfiguredMock(Product::class, [
            'getId' => 200,
            'getTypeId' => Constant::CONFIGURABLE_TYPE,
        ]);

        $products = [
            ['product_model' => $simpleProductMock],
            ['product_model' => $simpleProductMock],
        ];

        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $feedSpecificationMock->method('getIgnoreFields')->willReturn([]);

        $this->parentVariantResolverMock->expects($this->once())
            ->method('resolveParentProductForRow')
            ->with($products[0], $simpleProductMock)
            ->willReturn($parentProductMock);

        $this->configurableTypeMock->expects($this->once())
            ->method('getUsedProducts')
            ->with($parentProductMock)
            ->willReturn([]);

        $this->configurableHelperMock->expects($this->once())
            ->method('getOptions')
            ->with($parentProductMock, [])
            ->willReturn([
                'index' => [
                    '20' => [],
                ],
            ]);

        $result = $this->variantPosition->getData($products, $feedSpecificationMock);

        $this->assertSame(1, $result[0]['__variant_position']);
        $this->assertSame(1, $result[1]['__variant_position']);
    }
}
