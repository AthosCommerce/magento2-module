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

namespace AthosCommerce\Feed\Test\Unit\Model\Feed\DataProvider\Category;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use AthosCommerce\Feed\Model\Feed\DataProvider\Category\GetCategoriesByProductIds;

class GetCategoriesByProductIdsTest extends \PHPUnit\Framework\TestCase
{
    private $resourceConnectionMock;

    private $metadataPoolMock;

    private $getCategoriesByProductIds;

    public function setUp(): void
    {
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);
        $this->getCategoriesByProductIds = new GetCategoriesByProductIds(
            $this->resourceConnectionMock,
            $this->metadataPoolMock
        );
    }

    public function testExecute()
    {
        $entityMetadataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adapterMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapterMock);
        $adapterMock->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);
        $selectMock->expects($this->once())
            ->method('from')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->willReturn($entityMetadataMock);
        $entityMetadataMock->expects($this->any())
            ->method('getEntityTable')
            ->willReturn('test');
        $selectMock->expects($this->once())
            ->method('join')
            ->withAnyParameters()
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('where')
            ->withAnyParameters()
            ->willReturnSelf();
        $adapterMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'product_id' => 1,
                    'category_id' => 1,
                    'path' => 'test\path'
                ],
                [
                    'product_id' => 1,
                    'path' => 'test\path'
                ],
                [
                    'path' => 'test\path'
                ],
            ]);

        $this->assertSame(
            [
                1 => [
                    [
                        'category_id' => 1,
                        'path' => 'test\path'
                    ]
                ]
            ],
            $this->getCategoriesByProductIds->execute([])
        );
    }
}
