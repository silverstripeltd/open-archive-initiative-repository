<?php

namespace Terraformers\OpenArchive\Tests\Helpers;

use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Helpers\DateTimeHelper;

class DateTimeHelperTest extends SapphireTest
{

    public function testGetUtcStringFromLocal(): void
    {
        // We'll use Auckland time for our tests
        date_default_timezone_set('Pacific/Auckland');

        $localString = '2022-01-01 20:00:00';
        $utcString = DateTimeHelper::getUtcStringFromLocal($localString);
        // UTC is -13 when Daylight Savings is active
        $expectedUtc = '2022-01-01T07:00:00Z';

        $this->assertEquals($expectedUtc, $utcString);
    }

    public function testGetLocalStringFromUtc(): void
    {
        // We'll use Auckland time for our tests
        date_default_timezone_set('Pacific/Auckland');

        $utcString = '2022-01-01T07:00:00Z';
        $localString = DateTimeHelper::getLocalStringFromUtc($utcString);
        // UTC is -13 when Daylight Savings is active
        $expectedLocal = '2022-01-01 20:00:00';

        $this->assertEquals($expectedLocal, $localString);
    }

    public function testGetLocalStringFromUtcException(): void
    {
        $this->expectExceptionMessage('Invalid UTC date format provided');

        // Exception should be thrown here
        DateTimeHelper::getLocalStringFromUtc('2022-01-01 07:00:00');
    }

    public function testIsSupportedFormat(): void
    {
        // Invalid formats
        $this->assertFalse(DateTimeHelper::isSupportedUtcFormat('2020/01/01'));
        $this->assertFalse(DateTimeHelper::isSupportedUtcFormat('2020-01-01T'));
        $this->assertFalse(DateTimeHelper::isSupportedUtcFormat('2020-01-1'));
        $this->assertFalse(DateTimeHelper::isSupportedUtcFormat('2020-1-01'));
        $this->assertFalse(DateTimeHelper::isSupportedUtcFormat('2020/01/01T01:00:00Z'));
        $this->assertFalse(DateTimeHelper::isSupportedUtcFormat('2020/01/01T1:00:00Z'));
        $this->assertFalse(DateTimeHelper::isSupportedUtcFormat('2020-01-01T1:00:00Z'));
        // Valid formats
        $this->assertTrue(DateTimeHelper::isSupportedUtcFormat('2020-01-01'));
        $this->assertTrue(DateTimeHelper::isSupportedUtcFormat('2020-01-01T01:00:00Z'));
    }

}
