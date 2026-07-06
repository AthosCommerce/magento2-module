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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Stock;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\CompositeStockResolver;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockProviderInterface;
use AthosCommerce\Feed\Model\Feed\DataProvider\Stock\StockResolverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;

class CompositeStockResolverTest extends TestCase
{
    private $msiStockResolver;
    private $legacyStockResolver;
    private $loggerMock;
    private $compositeStockResolver;

    protected function setUp(): void
    {
        $this->msiStockResolver = $this->createMock(StockResolverInterface::class);
        $this->legacyStockResolver = $this->createMock(StockResolverInterface::class);
        $this->loggerMock = $this->createMock(AthosCommerceLogger::class);

        $resolvers = [
            'msi' => [
                'sortOrder' => 100,
                'objectInstance' => $this->msiStockResolver,
            ],
            'legacy' => [
                'sortOrder' => 1000,
                'objectInstance' => $this->legacyStockResolver,
            ],
        ];

        $this->compositeStockResolver = new CompositeStockResolver(
            $this->loggerMock,
            $resolvers
        );
    }

    public function testResolve(): void
    {
        $stockProviderMock = $this->createMock(StockProviderInterface::class);

        $this->msiStockResolver->expects($this->once())
            ->method('resolve')
            ->with(true)
            ->willReturn($stockProviderMock);

        $this->legacyStockResolver->expects($this->never())
            ->method('resolve');

        $this->assertSame($stockProviderMock, $this->compositeStockResolver->resolve(true));
    }

    public function testResolveExceptionCase(): void
    {
        $this->msiStockResolver->expects($this->once())
            ->method('resolve')
            ->with(true)
            ->willThrowException(new NoSuchEntityException(__('No MSI provider')));

        $this->legacyStockResolver->expects($this->once())
            ->method('resolve')
            ->with(true)
            ->willThrowException(new NoSuchEntityException(__('No legacy provider')));

        $this->loggerMock->expects($this->exactly(2))
            ->method('error');

        $this->expectException(NoSuchEntityException::class);

        $this->compositeStockResolver->resolve(true);
    }
}
