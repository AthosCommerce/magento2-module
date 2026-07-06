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
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use AthosCommerce\Feed\Model\Feed\DataProvider\AttributesProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentVariantResolver;
use AthosCommerce\Feed\Model\Feed\SystemFieldsList;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributesProviderTest extends TestCase
{
    /**
     * @var SystemFieldsList|MockObject
     */
    private $systemFieldsListMock;

    /**
     * @var ValueProcessor|MockObject
     */
    private $valueProcessorMock;

    /**
     * @var AttributesProviderInterface|MockObject
     */
    private $attributesProviderMock;

    /**
     * @var ParentVariantResolver|MockObject
     */
    private $parentVariantResolverMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var AttributesProvider
     */
    private $attributesProvider;

    protected function setUp(): void
    {
        $this->systemFieldsListMock = $this->createMock(SystemFieldsList::class);
        $this->valueProcessorMock = $this->createMock(ValueProcessor::class);
        $this->attributesProviderMock = $this->createMock(AttributesProviderInterface::class);
        $this->parentVariantResolverMock = $this->createMock(ParentVariantResolver::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->attributesProvider = new AttributesProvider(
            $this->systemFieldsListMock,
            $this->valueProcessorMock,
            $this->attributesProviderMock,
            $this->parentVariantResolverMock,
            $this->loggerMock
        );
    }

    public function testGetData(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);
        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $products = [
            ['product_model' => $productMock],
            ['product_model' => $productMock],
        ];

        $attributes = [$attributeMock];

        $this->attributesProviderMock->expects($this->once())
            ->method('getAttributes')
            ->with($feedSpecificationMock)
            ->willReturn($attributes);

        $this->systemFieldsListMock->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $attributeMock->method('getAttributeCode')
            ->willReturn('code');

        $productMock->method('getData')
            ->willReturn([
                'code' => 'data',
                'test1' => 'data2',
                'entity_id' => 12,
            ]);

        $this->parentVariantResolverMock->expects($this->exactly(2))
            ->method('resolveParentProductForRow')
            ->willReturn(null);

        $this->valueProcessorMock->expects($this->exactly(2))
            ->method('getValue')
            ->with(
                $attributeMock,
                'data',
                $productMock,
                $feedSpecificationMock
            )
            ->willReturnOnConsecutiveCalls('code', 'code1');

        $result = $this->attributesProvider->getData($products, $feedSpecificationMock);

        $this->assertSame(
            [
                [
                    'product_model' => $productMock,
                    'code' => 'code',
                    'test1' => 'data2',
                    'entity_id' => 12,
                ],
                [
                    'product_model' => $productMock,
                    'code' => 'code1',
                    'test1' => 'data2',
                    'entity_id' => 12,
                ],
            ],
            $result
        );
    }
}
