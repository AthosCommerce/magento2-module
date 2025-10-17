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

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\View\LayoutInterface;
use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchesConfigurable;
use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Configurable\DataProvider;
use AthosCommerce\Feed\Model\Feed\DataProvider\JsonConfigProvider;

class JsonConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    private $layoutMock;

    private $dataProviderMock;

    private $jsonConfigProvider;

    public function setUp(): void
    {
        $this->layoutMock = $this->createMock(LayoutInterface::class);
        $this->dataProviderMock = $this->createMock(DataProvider::class);
        $this->jsonConfigProvider = new JsonConfigProvider($this->layoutMock, $this->dataProviderMock);
    }

    public function testGetData()
    {
        $configurableBlockMock = $this->createMock(Configurable::class);
        $configurableSwatchesBlockMock = $this->createMock(SwatchesConfigurable::class);
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configurableProductMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $products = [
            [
                'product_model' => $productMock,
            ],
            [
                'product_model' => $configurableProductMock,
            ],
        ];
        $configurableProductMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeJSONConfig')
            ->willReturn(true);
        $this->dataProviderMock->expects($this->once())
            ->method('getAllChildProducts')
            ->with($products, $feedSpecificationMock)
            ->willReturn([]);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');
        $configurableProductMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('configurable');
        $this->layoutMock->expects($this->at(0))
            ->method('createBlock')
            ->with(Configurable::class)
            ->willReturn($configurableBlockMock);
        $this->layoutMock->expects($this->at(1))
            ->method('createBlock')
            ->with(SwatchesConfigurable::class)
            ->willReturn($configurableSwatchesBlockMock);
        $configurableBlockMock->expects($this->once())
            ->method('unsetData')
            ->willReturnSelf();
        $configurableSwatchesBlockMock->expects($this->once())
            ->method('unsetData')
            ->willReturnSelf();
        $configurableSwatchesBlockMock->expects($this->once())
            ->method('setProduct')
            ->with($configurableProductMock)
            ->willReturnSelf();
        $configurableBlockMock->expects($this->once())
            ->method('getJsonConfig')
            ->willReturn('{testc: testc}');
        $configurableSwatchesBlockMock->expects($this->once())
            ->method('getJsonSwatchConfig')
            ->willReturn('{testsw: testsw}');

        $this->assertSame(
            [
                [
                    'product_model' => $productMock
                ],
                [
                    'product_model' => $configurableProductMock,
                    'json_config' => '{testc: testc}',
                    'swatch_json_config' => '{testsw: testsw}'
                ]
            ],
            $this->jsonConfigProvider->getData($products, $feedSpecificationMock)
        );
    }
}
