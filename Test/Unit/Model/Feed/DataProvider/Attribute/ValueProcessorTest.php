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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Attribute;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValueProcessorTest extends TestCase
{
    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var AthosCommerceLogger|MockObject
     */
    private $loggerMock;

    /**
     * @var ValueProcessor
     */
    private $valueProcessor;

    protected function setUp(): void
    {
        $this->jsonMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->valueProcessor = new ValueProcessor(
            $this->jsonMock,
            $this->loggerMock
        );
    }

    public function testGetValue(): void
    {
        $attributeMock = $this->createMock(Attribute::class);
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $attributeMock->method('getAttributeCode')->willReturn('description');
        $attributeMock->method('usesSource')->willReturn(false);

        $value = new Phrase('Test phrase');

        $this->assertSame(
            'Test phrase',
            $this->valueProcessor->getValue(
                $attributeMock,
                $value,
                $productMock,
                $feedSpecificationMock
            )
        );
    }

    public function testGetValueOnCache(): void
    {
        $attributeMock = $this->createMock(Attribute::class);
        $sourceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getAllOptions'])
            ->getMock();
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $attributeMock->method('getAttributeCode')->willReturn('color');
        $attributeMock->method('usesSource')->willReturn(true);
        $attributeMock->method('getFrontendInput')->willReturn('select');
        $attributeMock->expects($this->once())
            ->method('getSource')
            ->willReturn($sourceMock);

        $productMock->method('getStoreId')->willReturn(1);

        $sourceMock->expects($this->once())
            ->method('getAllOptions')
            ->willReturn([
                ['value' => '1', 'label' => 'Red'],
                ['value' => '2', 'label' => 'Blue'],
            ]);

        $first = $this->valueProcessor->getValue(
            $attributeMock,
            '1',
            $productMock,
            $feedSpecificationMock
        );

        $second = $this->valueProcessor->getValue(
            $attributeMock,
            '2',
            $productMock,
            $feedSpecificationMock
        );

        $this->assertSame('Red', $first);
        $this->assertSame('Blue', $second);
    }

    public function testGetValueExceptionLikeUnexpectedObjectReturnsOriginalValue(): void
    {
        $attributeMock = $this->createMock(Attribute::class);
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $unexpectedValue = new \stdClass();

        $attributeMock->method('getAttributeCode')->willReturn('custom_object');
        $attributeMock->method('usesSource')->willReturn(false);

        $productMock->method('getEntityId')->willReturn(10);

        $this->loggerMock->expects($this->once())
            ->method('error');

        $result = $this->valueProcessor->getValue(
            $attributeMock,
            $unexpectedValue,
            $productMock,
            $feedSpecificationMock
        );

        $this->assertSame($unexpectedValue, $result);
    }
}
