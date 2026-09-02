<?php
declare(strict_types=1);

namespace AthosCommerce\Feed\Test\Unit\Helper;

use AthosCommerce\Feed\Helper\Utils;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function testGetDateRangeReturnsFalseForAll(): void
    {
        $this->assertFalse(Utils::getDateRange('All'));
    }

    public function testGetDateRangeReturnsArrayForSingleDate(): void
    {
        $this->assertSame(['2026-08-01'], Utils::getDateRange('2026-08-01'));
    }

    public function testGetDateRangeReturnsArrayForTwoDates(): void
    {
        $this->assertSame(
            ['2026-08-01', '2026-08-31'],
            Utils::getDateRange('2026-08-01,2026-08-31')
        );
    }

    public function testValidateDateRangeReturnsTrueForAllInCurrentBehavior(): void
    {
        $this->assertTrue(Utils::validateDateRange('All'));
    }

    public function testValidateDateRangeReturnsTrueForSingleValidDate(): void
    {
        $this->assertTrue(Utils::validateDateRange('2026-08-01'));
    }

    public function testValidateDateRangeReturnsTrueForTwoValidDates(): void
    {
        $this->assertTrue(Utils::validateDateRange('2026-08-01,2026-08-31'));
    }

    public function testValidateDateRangeReturnsFalseForInvalidFromDate(): void
    {
        $this->assertFalse(Utils::validateDateRange('2026-02-30,2026-08-31'));
    }

    public function testValidateDateRangeReturnsFalseForInvalidToDate(): void
    {
        $this->assertFalse(Utils::validateDateRange('2026-08-01,2026-13-01'));
    }

    public function testValidateDateRangeReturnsFalseForMalformedDate(): void
    {
        $this->assertFalse(Utils::validateDateRange('2026/08/01,2026-08-31'));
    }

    public function testValidateDateRangeReturnsFalseWhenFromDateIsAfterToDate(): void
    {
        $this->assertFalse(Utils::validateDateRange('2026-08-31,2026-08-01'));
    }

    public function testGetRowRangeReturnsFalseForAll(): void
    {
        $this->assertFalse(Utils::getRowRange('All'));
    }

    public function testGetRowRangeReturnsFalseForMalformedRange(): void
    {
        $this->assertFalse(Utils::getRowRange('1'));
    }

    public function testGetRowRangeConvertsToZeroBasedOffsetAndCount(): void
    {
        $this->assertSame([0, 10], Utils::getRowRange('1,10'));
        $this->assertSame([9, 3], Utils::getRowRange('10,3'));
    }

    public function testValidateRowRangeRejectsAll(): void
    {
        $this->assertFalse(Utils::validateRowRange('All'));
    }

    public function testValidateRowRangeRejectsMalformedRanges(): void
    {
        $this->assertFalse(Utils::validateRowRange('1'));
        $this->assertFalse(Utils::validateRowRange('1,2,3'));
        $this->assertFalse(Utils::validateRowRange(''));
    }

    public function testValidateRowRangeRejectsNonNumericValues(): void
    {
        $this->assertFalse(Utils::validateRowRange('a,10'));
        $this->assertFalse(Utils::validateRowRange('1,b'));
        $this->assertFalse(Utils::validateRowRange('1.5,10'));
    }

    public function testValidateRowRangeRejectsZeroAndNegativeValues(): void
    {
        $this->assertFalse(Utils::validateRowRange('0,10'));
        $this->assertFalse(Utils::validateRowRange('1,0'));
        $this->assertFalse(Utils::validateRowRange('-1,10'));
    }

    public function testValidateRowRangeAcceptsValidRanges(): void
    {
        $this->assertTrue(Utils::validateRowRange('1,1'));
        $this->assertTrue(Utils::validateRowRange('25,500'));
    }

    public function testPlusOneDay(): void
    {
        $this->assertSame('2026-09-01', Utils::plusOneDay('2026-08-31', 'Y-m-d'));
    }
}
