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

declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\Resolver;

use AthosCommerce\Feed\Api\Data\FeedSpecificationInterface;
use AthosCommerce\Feed\Model\Feed\Resolver\ParentOverrideResolver;
use PHPUnit\Framework\TestCase;

class ParentOverrideResolverTest extends TestCase
{
    public function testParentProductTypeIsOverriddenForParentRows(): void
    {
        $resolver = new ParentOverrideResolver();

        $specification = $this->createMock(FeedSpecificationInterface::class);
        $specification->method('getIgnoreFields')->willReturn([]);

        $rows = [[
            '__is_belong_to_parent' => true,
            'parent_type_id' => 'grouped',
            'product_type' => 'simple',
        ]];

        $result = $resolver->process($rows, $specification);

        $this->assertSame('grouped', $result[0]['product_type']);
    }
}
