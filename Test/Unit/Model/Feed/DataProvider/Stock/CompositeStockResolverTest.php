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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Stock;

use AthosCommerce\Feed\Exception\NoSuchEntityException;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\CompositeStockResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockResolverInterface;

class CompositeStockResolverTest extends \PHPUnit\Framework\TestCase
{
    private $msiStockResolver;

    private $legacyStockResolver;

    private $compositeStockResolver;

    public function setUp(): void
    {
        $this->msiStockResolver = $this->createMock(StockResolverInterface::class);
        $this->legacyStockResolver = $this->createMock(StockResolverInterface::class);
        $resolvers = [
            'msi' => [
                'sortOrder' => 100,
                'objectInstance' => $this->msiStockResolver
            ],
            'legacy' => [
                'sortOrder' => 1000,
                'objectInstance' => $this->legacyStockResolver
            ]
        ];
        $this->compositeStockResolver = new CompositeStockResolver($resolvers);
    }

    public function testResolve()
    {
        $stockProviderInterfaceMock = $this->getMockForAbstractClass(StockProviderInterface::class);
        $this->msiStockResolver->expects($this->any())
            ->method('resolve')
            ->willReturn($stockProviderInterfaceMock);

        $this->assertSame($stockProviderInterfaceMock, $this->compositeStockResolver->resolve());
    }

    public function testResolveExceptionCase()
    {
        $this->msiStockResolver->expects($this->any())
            ->method('resolve')
            ->willThrowException(new NoSuchEntityException());
        $this->expectException(NoSuchEntityException::class);

        $this->compositeStockResolver->resolve();
    }
}
