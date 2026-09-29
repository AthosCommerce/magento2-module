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

use AthosCommerce\Feed\Helper\S3UrlValidator;
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
     * @var S3UrlValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $s3UrlValidatorMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->createValidationResultMock = $this->createMock(CreateValidationResult::class);
        $this->urlValidatorMock = $this->createMock(Url::class);
        $this->s3UrlValidatorMock = $this->createMock(S3UrlValidator::class);
    }

    /**
     * @param array $fields
     * @param array $payload
     * @param bool $fieldRequired
     * @param array $expectedValidatedValues
     * @param array $urlValidationResults
     * @param array $s3ValidationResults
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
        array $s3ValidationResults,
        array $expectedErrors
    ): void {
        $callIndex = 0;
        $s3CallIndex = 0;
        $this->urlValidatorMock->expects($this->exactly(count($expectedValidatedValues)))
            ->method('isValid')
            ->willReturnCallback(
                function (
                    $value,
                    array $schemes
                ) use (
                    &$callIndex,
                    $expectedValidatedValues,
                    $urlValidationResults
                ): bool {
                    $this->assertSame(['https'], $schemes);
                    $this->assertSame((string)$expectedValidatedValues[$callIndex], (string)$value);
                    $result = $urlValidationResults[$callIndex];
                    $callIndex++;
                    return $result;
                }
            );
        $this->s3UrlValidatorMock->expects($this->exactly(count($s3ValidationResults)))
            ->method('validate')
            ->willReturnCallback(
                function (string $value) use (
                    &$s3CallIndex,
                    $expectedValidatedValues,
                    $s3ValidationResults
                ): bool {
                    $this->assertSame((string)$expectedValidatedValues[$s3CallIndex], $value);
                    $result = $s3ValidationResults[$s3CallIndex];
                    $s3CallIndex++;

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
            $this->s3UrlValidatorMock,
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
        $validS3Url1 = self::buildSignedS3Url(
            'my-bucket.s3.us-east-1.amazonaws.com',
            'file.json.gz'
        );
        $validS3Url2 = self::buildSignedS3Url(
            'my-bucket.s3.amazonaws.com',
            'file-catalog.txt.gz'
        );

        return [
            'required-presignedurl-valid' => [
                ['preSignedUrl'],
                ['preSignedUrl' => $validS3Url1],
                true,
                [$validS3Url1],
                [true],
                [true],
                [],
            ],
            'required-presignedurl-missing' => [
                ['preSignedUrl'],
                [],
                true,
                [],
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
                [],
            ],
            'optional-catalogpresignedurl-invalid-domain-fails' => [
                ['catalogPreSignedUrl'],
                ['catalogPreSignedUrl' => 'https://fakeamazonaws.com/file.txt'],
                false,
                ['https://fakeamazonaws.com/file.txt'],
                [true],
                [false],
                [(string) __('"catalogPreSignedUrl" field value must contain valid bucket url')],
            ],
            'http-s3-url-fails' => [
                ['catalogPreSignedUrl'],
                ['catalogPreSignedUrl' => 'http://my-bucket.s3.us-east-1.amazonaws.com/disabled-all-flags.json'],
                false,
                ['http://my-bucket.s3.us-east-1.amazonaws.com/disabled-all-flags.json'],
                [false],
                [],
                [(string) __('"catalogPreSignedUrl" field value must be valid url address')],
            ],
            'spoofed-domain-fakeamazonaws-com' => [
                ['preSignedUrl'],
                ['preSignedUrl' => 'https://fakeamazonaws.com/disabled-all-flags.json'],
                true,
                ['https://fakeamazonaws.com/disabled-all-flags.json'],
                [true],
                [false],
                [(string) __('"preSignedUrl" field value must contain valid bucket url')],
            ],
            'non-s3-aws-service-host-fails' => [
                ['preSignedUrl'],
                ['preSignedUrl' => 'https://sts.amazonaws.com/disabled-all-flags.json'],
                true,
                ['https://sts.amazonaws.com/disabled-all-flags.json'],
                [true],
                [false],
                [(string) __('"preSignedUrl" field value must contain valid bucket url')],
            ],
        ];
    }

    /**
     * Build a deterministic signed S3 test URL.
     *
     * @param string $host
     * @param string $fileName
     * @return string
     */
    private static function buildSignedS3Url(string $host, string $fileName): string
    {
        return sprintf(
            'https://%1$s/%2$s?X-Amz-Algorithm=AWS4-HMAC-SHA256'
            . '&X-Amz-Credential=AKIAIOSFODNN7EXAMPLE%%2F20260101%%2Fus-east-1%%2Fs3%%2Faws4_request'
            . '&X-Amz-Date=20260101T000000Z'
            . '&X-Amz-Expires=86400'
            . '&X-Amz-SignedHeaders=host'
            . '&X-Amz-Signature=0000000000000000000000000000000000000000000000000000000000000000',
            $host,
            $fileName
        );
    }
}
