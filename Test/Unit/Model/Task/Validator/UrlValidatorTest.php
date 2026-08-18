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

namespace AthosCommerce\Feed\Test\Unit\Model\Task\Validator;

use AthosCommerce\Feed\Model\Task\Validator\CreateValidationResult;
use AthosCommerce\Feed\Model\Task\Validator\UrlValidator;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validator\Url;

class UrlValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreateValidationResult
     */
    private $createValidationResultMock;

    /**
     * @var Url
     */
    private $urlValidatorMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->createValidationResultMock = $this->createMock(CreateValidationResult::class);
        $this->urlValidatorMock = $this->createMock(Url::class);
    }

    /**
     * @param array $fields
     * @param array $payload
     * @param bool $fieldRequired
     * @param array $expectedValidatedValues
     * @param array $urlValidationResults
     * @param array $expectedErrors
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        array $fields,
        array $payload,
        bool $fieldRequired,
        array $expectedValidatedValues,
        array $urlValidationResults,
        array $expectedErrors
    ): void {
        $callIndex = 0;
        $this->urlValidatorMock->expects($this->exactly(count($expectedValidatedValues)))
            ->method('isValid')
            ->willReturnCallback(
                function ($value, array $schemes) use (
                    &$callIndex,
                    $expectedValidatedValues,
                    $urlValidationResults
                ): bool {
                    $this->assertSame(['http', 'https'], $schemes);
                    $this->assertSame((string)$expectedValidatedValues[$callIndex], (string)$value);
                    $result = $urlValidationResults[$callIndex];
                    $callIndex++;
                    return $result;
                }
            );

        $resultValidationMock = $this->getMockBuilder(ValidationResult::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->createValidationResultMock->expects($this->once())
            ->method('create')
            ->with($expectedErrors)
            ->willReturn($resultValidationMock);

        $resultValidationMock->expects($this->any())
            ->method('getErrors')
            ->willReturn($expectedErrors);

        $validator = new UrlValidator(
            $this->createValidationResultMock,
            $this->urlValidatorMock,
            $fields,
            $fieldRequired
        );

        $this->assertSame(
            count($expectedErrors),
            count($validator->validate($payload)->getErrors())
        );
    }

    /**
     * @return array[]
     */
    public static function validateDataProvider(): array
    {
        $validS3Url1 = 'https://my-bucket.s3.us-east-1.amazonaws.com/file.json.gz?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAIOSFODNN7EXAMPLE%2F20260101%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20260101T000000Z&X-Amz-Expires=86400&X-Amz-SignedHeaders=host&X-Amz-Signature=0000000000000000000000000000000000000000000000000000000000000000';
        $validS3Url2 = 'https://my-bucket.s3.amazonaws.com/file-catalog.txt.gz?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAIOSFODNN7EXAMPLE%2F20260101%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20260101T000000Z&X-Amz-Expires=86400&X-Amz-SignedHeaders=host&X-Amz-Signature=0000000000000000000000000000000000000000000000000000000000000000';

        return [
            'required-presignedurl-valid' => [
                ['preSignedUrl'],
                ['preSignedUrl' => $validS3Url1],
                true,
                [$validS3Url1],
                [true],
                [],
            ],
            'required-presignedurl-missing' => [
                ['preSignedUrl'],
                [],
                true,
                [],
                [],
                [(string) __('preSignedUrl field is required')],
            ],
            'optional-catalogpresignedurl-provided-and-valid' => [
                ['catalogPreSignedUrl'],
                ['catalogPreSignedUrl' => $validS3Url2],
                false,
                [$validS3Url2],
                [true],
                [],
            ],
            'optional-catalogpresignedurl-missing-passes' => [
                ['catalogPreSignedUrl'],
                [],
                false,
                [],
                [],
                [],
            ],
            'optional-catalogpresignedurl-invalid-domain-fails' => [
                ['catalogPreSignedUrl'],
                ['catalogPreSignedUrl' => 'https://fakeamazonaws.com/file.txt'],
                false,
                ['https://fakeamazonaws.com/file.txt'],
                [true],
                [(string) __('"catalogPreSignedUrl" field value must contain valid bucket url')],
            ],
            'spoofed-domain-amazonaws-dummy-com' => [
                ['catalogPreSignedUrl'],
                ['catalogPreSignedUrl' => 'http://amazonaws.dummy.com/disabled-all-flags.json'],
                false,
                ['http://amazonaws.dummy.com/disabled-all-flags.json'],
                [true],
                [(string) __('"catalogPreSignedUrl" field value must contain valid bucket url')],
            ],
            'spoofed-domain-fakeamazonaws-com' => [
                ['preSignedUrl'],
                ['preSignedUrl' => 'https://fakeamazonaws.com/disabled-all-flags.json'],
                true,
                ['https://fakeamazonaws.com/disabled-all-flags.json'],
                [true],
                [(string) __('"preSignedUrl" field value must contain valid bucket url')],
            ],
        ];
    }
}