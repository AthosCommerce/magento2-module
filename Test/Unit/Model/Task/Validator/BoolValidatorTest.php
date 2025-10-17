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

namespace AthosCommerce\Feed\Test\Unit\Model\Task\Validator;

use Magento\Framework\Validation\ValidationResult;
use AthosCommerce\Feed\Model\Task\Validator\BoolValidator;
use AthosCommerce\Feed\Model\Task\Validator\CreateValidationResult;

class BoolValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreateValidationResult
     */
    private $createValidationResultMock;

    private $fields = [
        'customerId',
        'includeOutOfStock',
    ];

    private $boolValidator;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->createValidationResultMock = $this->createMock(CreateValidationResult::class);
        $this->boolValidator = new BoolValidator(
            $this->createValidationResultMock,
            $this->fields,
            true
        );
    }

    /**
     * @param array $payload
     * @param int $result
     * @param array $errors
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $payload, int $result, array $errors)
    {
        $resultValidationMock = $this->getMockBuilder(ValidationResult::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->createValidationResultMock->expects($this->once())
            ->method('create')
            ->willReturn($resultValidationMock);
        $resultValidationMock->expects($this->any())
            ->method('getErrors')
            ->withAnyParameters()
            ->willReturn($errors);

        $this->assertSame(
            $result,
            count($this->boolValidator->validate($payload)->getErrors())
        );
    }

    /**
     * @return array[]
     */
    public function validateDataProvider(): array
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
                0,
                []
            ],
            [
                [
                    'customerId' => 2,
                    'childFields' => [
                        'manufacturer',
                        'country_of_manufacturer'
                    ]
                ],
                1,
                [1]
            ]
        ];
    }
}
