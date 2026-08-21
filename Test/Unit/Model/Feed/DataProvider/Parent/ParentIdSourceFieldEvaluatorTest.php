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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Parent;

use AthosCommerce\Feed\Logger\AthosCommerceLogger;
use AthosCommerce\Feed\Model\Feed\DataProvider\Parent\ParentIdSourceFieldEvaluator;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class ParentIdSourceFieldEvaluatorTest extends TestCase
{
    public function testExecuteUsesEntityIdWhenIdentifierIsBlank(): void
    {
        $logger = $this->createMock(AthosCommerceLogger::class);
        $product = $this->createMock(Product::class);

        $product->expects($this->exactly(1))
            ->method('getDataUsingMethod')
            ->with('entity_id')
            ->willReturn(110087);

        $evaluator = new ParentIdSourceFieldEvaluator($logger);
        $result = $evaluator->execute($product, null);

        $this->assertSame('110087', $result);
    }

    public function testExecuteFallsBackToRowIdWhenEntityIdIsMissing(): void
    {
        $logger = $this->createMock(AthosCommerceLogger::class);
        $product = $this->createMock(Product::class);

        $product->expects($this->exactly(2))
            ->method('getDataUsingMethod')
            ->willReturnCallback(static function (string $field) {
                if ($field === 'entity_id') {
                    return null;
                }

                if ($field === 'row_id') {
                    return 112763;
                }

                return null;
            });

        $evaluator = new ParentIdSourceFieldEvaluator($logger);
        $result = $evaluator->execute($product, '   ');

        $this->assertSame('112763', $result);
    }
}
