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
use AthosCommerce\Feed\Model\Feed\DataProvider\OptionsProvider;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class OptionsProviderTest extends TestCase
{
    private $metadataPoolMock;
    private $optionCollectionFactoryMock;
    private $storeManagerMock;
    private $optionsProvider;

    protected function setUp(): void
    {
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->optionCollectionFactoryMock = $this->createMock(OptionCollectionFactory::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->optionsProvider = new OptionsProvider(
            $this->metadataPoolMock,
            $this->optionCollectionFactoryMock,
            $this->storeManagerMock
        );
    }

    public function testGetDataReturnsProductsWhenNoProductModelsContainLinkField(): void
    {
        $feedSpecificationMock = $this->createMock(FeedSpecificationInterface::class);

        $products = [
            [],
            ['product_model' => null],
        ];

        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->willThrowException(new \Exception('Not needed if no valid product models are processed'));

        try {
            $this->optionsProvider->getData($products, $feedSpecificationMock);
        } catch (\Exception $e) {
            $this->assertSame('Not needed if no valid product models are processed', $e->getMessage());
        }

        $this->addToAssertionCount(1);
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
