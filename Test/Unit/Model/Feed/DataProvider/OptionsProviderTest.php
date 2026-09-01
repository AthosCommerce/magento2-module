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

namespace Magento\Catalog\Model\ResourceModel\Product\Option {
    if (!class_exists(CollectionFactory::class, false)) {
        class CollectionFactory
        {
            public function create()
            {
                return null;
            }
        }
    }
}

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider {

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\OptionsProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class OptionsProviderTest extends TestCase
{
    private $metadataPoolMock;
    private $optionCollectionFactoryMock;
    private $storeManagerMock;
    private $loggerMock;
    private $optionsProvider;

    protected function setUp(): void
    {
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->optionCollectionFactoryMock = $this->createMock(OptionCollectionFactory::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $this->optionsProvider = new OptionsProvider(
            $this->metadataPoolMock,
            $this->optionCollectionFactoryMock,
            $this->storeManagerMock,
            $this->loggerMock
        );
    }

    public function testGetDataReturnsProductsWhenNoProductModelsContainLinkField(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $products = [
            [],
            ['product_model' => null],
        ];

        $metadataMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getLinkField'])
            ->getMock();

        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($metadataMock);

        $metadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn('row_id');

        $this->optionCollectionFactoryMock->expects($this->never())
            ->method('create');

        $this->storeManagerMock->expects($this->never())
            ->method('getStore');

        $this->assertSame($products, $this->optionsProvider->getData($products, $feedSpecificationMock));
    }

    public function testResetDoesNothing(): void
    {
        $this->optionsProvider->reset();
        $this->addToAssertionCount(1);
    }

    public function testResetAfterFetchItemsDoesNothing(): void
    {
        $this->optionsProvider->resetAfterFetchItems();
        $this->addToAssertionCount(1);
    }
}
}
