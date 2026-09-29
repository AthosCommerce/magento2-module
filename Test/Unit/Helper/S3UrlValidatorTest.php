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

namespace AthosCommerce\Feed\Test\Unit\Helper;

use AthosCommerce\Feed\Helper\S3UrlValidator;

class S3UrlValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isValidDataProvider
     * @param string $url
     * @param bool $expectedResult
     * @return void
     */
    public function testValidate(string $url, bool $expectedResult): void
    {
        $this->assertSame($expectedResult, (new S3UrlValidator())->validate($url));
    }

    /**
     * @return array[]
     */
    public static function isValidDataProvider(): array
    {
        $validRegionalUrl = 'https://test-athos-feed-bucket.s3.us-east-1.amazonaws.com/file.json.gz?X-Amz-Signature=test';
        $validGlobalUrl = 'https://test-athos-feed-bucket.s3.amazonaws.com/file.json.gz?X-Amz-Signature=test';

        return [
            'accepts-regional-s3-url' => [$validRegionalUrl, true],
            'accepts-global-s3-url' => [$validGlobalUrl, true],
            'rejects-http-url' => ['http://test-athos-feed-bucket.s3.us-east-1.amazonaws.com/file.json.gz', false],
            'rejects-non-s3-aws-host' => ['https://sts.amazonaws.com/', false],
            'rejects-spoofed-amazonaws-host' => ['https://fakeamazonaws.com/file.json.gz', false],
            'rejects-user-info' => ['https://user@test-athos-feed-bucket.s3.us-east-1.amazonaws.com/file.json.gz', false],
            'rejects-custom-port' => ['https://test-athos-feed-bucket.s3.us-east-1.amazonaws.com:443/file.json.gz', false],
        ];
    }
}
