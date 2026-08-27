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

namespace AthosCommerce\Feed\Test\Unit\Helper;

use AthosCommerce\Feed\Helper\SensitiveDataMasker;
use PHPUnit\Framework\TestCase;

class SensitiveDataMaskerTest extends TestCase
{
    public function testMaskRedactsDefaultSensitiveKeysRecursively(): void
    {
        $input = [
            'secretKey' => 'abc',
            'feedSpecification' => [
                'preSignedUrl' => 'https://example.com/signed',
                'catalogPreSignedUrl' => 'https://example.com/catalog-signed',
                'other' => 'value',
            ],
            'nullableSecret' => null,
        ];

        $result = SensitiveDataMasker::mask($input);

        $this->assertSame('****', $result['secretKey']);
        $this->assertSame('****', $result['feedSpecification']['preSignedUrl']);
        $this->assertSame('****', $result['feedSpecification']['catalogPreSignedUrl']);
        $this->assertSame('value', $result['feedSpecification']['other']);
        $this->assertNull($result['nullableSecret']);
    }

    public function testMaskReturnsNonArrayValuesUnchanged(): void
    {
        $this->assertSame('plain', SensitiveDataMasker::mask('plain'));
        $this->assertSame(1, SensitiveDataMasker::mask(1));
        $this->assertNull(SensitiveDataMasker::mask(null));
    }
}

