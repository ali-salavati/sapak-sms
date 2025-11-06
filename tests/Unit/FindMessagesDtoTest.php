<?php

namespace Sapak\Sms\Tests\Unit;

use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Sapak\Sms\DTOs\Requests\FindMessages;

/**
 * Unit tests for the FindMessages DTO.
 *
 * Validates pagination limits, null filtering, and DateTime to Jalali conversion.
 */
class FindMessagesDtoTest extends TestCase
{
    public function test_it_throws_exception_if_page_size_is_over_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed 100');

        new FindMessages(pageSize: 101);
    }

    public function test_to_array_filters_null_values(): void
    {
        $dto = new FindMessages(
            pageNumber: 2,
            smsNumber: '985000'
        );

        $expected = [
            'pageNumber' => 2,
            'smsNumber' => '985000',
        ];

        $this->assertEquals($expected, $dto->toArray());
    }

    public function test_it_converts_datetime_to_api_format(): void
    {
        // Known Gregorian date for testing
        $gregorianDate = new DateTime('2023-11-21 10:30:00');

        // Expected Jalali string output
        $expectedJalaliString = '1402-08-30 10:30:00';

        $dto = new FindMessages(
            fromDate: $gregorianDate,
            toDate: $gregorianDate
        );

        $this->assertEquals(
            [
                'fromDate' => $expectedJalaliString,
                'toDate' => $expectedJalaliString,
            ],
            $dto->toArray()
        );
    }
}
