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

namespace AthosCommerce\Feed\Test\Unit\Model\Task\GenerateFeed;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use AthosCommerce\Feed\Api\Data\TaskSearchResultsInterface;
use AthosCommerce\Feed\Api\TaskRepositoryInterface;
use AthosCommerce\Feed\Model\Task;
use AthosCommerce\Feed\Model\Task\GenerateFeed\UniqueChecker;

class UniqueCheckerTest extends TestCase
{
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepositoryMock;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilderMock;

    private $uniqueChecker;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->taskRepositoryMock = $this->createMock(TaskRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->uniqueChecker = new UniqueChecker(
            $this->taskRepositoryMock,
            $this->searchCriteriaBuilderMock
        );
    }

    /**
     * @return void
     * @dataProvider checkDataProvider
     * @throws LocalizedException
     */
    public function testCheck(array $payload, bool $result)
    {
        $data = [
            'stores' => 'default',
            'customerId' => 1,
            'childFields' => [
                'manufacturer',
                'country_of_manufacturer'
            ],
            'includeOutOfStock' => 1
        ];

        $searchResultsMock = $this->getMockBuilder(TaskSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $taskMock = $this->getMockBuilder(Task::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteriaMock = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilter')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $this->taskRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultsMock);
        $searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$taskMock]);

        $taskMock->expects($this->once())
            ->method('getPayload')
            ->willReturn($data);

        $this->assertSame($result, $this->uniqueChecker->check($payload));
    }

    /**
     * @return array[]
     */
    public function checkDataProvider(): array
    {
        return [
            [
                [
                    'stores' => 'default',
                    'customerId' => 1,
                    'childFields' => [
                        'manufacturer',
                        'country_of_manufacturer'
                    ],
                    'includeOutOfStock' => 1
                ],
                false
            ],
            [
                [
                    'stores' => 'default',
                    'customerId' => 2,
                    'childFields' => [
                        'manufacturer',
                        'country_of_manufacturer'
                    ],
                    'includeOutOfStock' => 1
                ],
                true
            ],
            [
                [
                    'stores' => 'web',
                    'customerId' => 2,
                    'childFields' => [
                        'country_of_manufacturer',
                        'manufacturer'
                    ],
                    'includeOutOfStock' => 1
                ],
                true
            ],
            [
                [
                    'stores' => 'default',
                    'customerId' => 1,
                    'childFields' => [
                        'country_of_manufacturer',
                        'manufacturer'
                    ],
                    'includeOutOfStock' => 1
                ],
                false
            ]
        ];
    }
}
