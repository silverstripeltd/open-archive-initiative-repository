<?php

namespace Terraformers\OpenArchive\Tests\Helpers;

use ReflectionClass;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use Terraformers\OpenArchive\Controllers\OaiController;
use Terraformers\OpenArchive\Helpers\ResumptionTokenHelper;

class ResumptionTokenHelperTest extends SapphireTest
{

    public function testResumptionTokenWithExpiry(): void
    {
        // We'll use Auckland time for our tests
        date_default_timezone_set('Pacific/Auckland');

        DBDatetime::set_mock_now('2020-01-01 13:00:00');

        $verb = 'ListRecords';
        $page = 3;
        $from = '2022-01-01T01:00:00Z';
        $until = '2022-01-01T02:00:00Z';
        $set = 2;
        // -13 for UTC+0, but +1 for the duration of the expiry
        $expiry = '2020-01-01T01:00:00Z';

        $expectedParts = [
            'verb' => $verb,
            'page' => $page,
            'from' => $from,
            'until' => $until,
            'set' => $set,
            'expiry' => $expiry,
        ];

        // Generate our Token
        $token = ResumptionTokenHelper::generateResumptionToken($verb, $page, $from, $until, $set);

        // Now decode that Token
        $reflection = new ReflectionClass(ResumptionTokenHelper::class);
        $method = $reflection->getMethod('getResumptionTokenParts');
        $method->setAccessible(true);
        $resumptionParts = $method->invoke(null, $token);

        // And check that the Token that was encoded and decoded matches our expected values
        $this->assertEquals(ksort($expectedParts), ksort($resumptionParts));
        // Check that our "get page number" method works as well
        $this->assertEquals(
            $page,
            ResumptionTokenHelper::getPageFromResumptionToken($token, $verb, $from, $until, $set)
        );
        // Check that our "get expiry" method works as well
        $this->assertEquals($expiry, ResumptionTokenHelper::getExpiryFromResumptionToken($token));
    }

    public function testResumptionTokenHasExpired(): void
    {
        // We'll use Auckland time for our tests
        date_default_timezone_set('Pacific/Auckland');

        $this->expectExceptionMessage('Invalid resumption token');

        DBDatetime::set_mock_now('2020-01-01 13:00:00');

        $verb = 'ListRecords';
        $page = 3;
        $from = '2022-01-01T01:00:00Z';
        $until = '2022-01-01T02:00:00Z';
        $set = 2;

        // Generate our Token
        $token = ResumptionTokenHelper::generateResumptionToken($verb, $page, $from, $until, $set);

        // Now set the time to a couple hours later. This should invalidate the Resumption Token
        DBDatetime::set_mock_now('2020-01-01 15:00:00');

        // This should throw an Exception
        ResumptionTokenHelper::getPageFromResumptionToken($token, $verb, $from, $until, $set);
    }

    public function testResumptionTokenNoExpiry(): void
    {
        OaiController::config()->set('resumption_token_expiry', null);

        $verb = 'ListRecords';
        $page = 3;
        $from = '2022-01-01T01:00:00Z';
        $until = '2022-01-01T02:00:00Z';
        $set = 2;

        $expectedParts = [
            'verb' => $verb,
            'page' => $page,
            'from' => $from,
            'until' => $until,
            'set' => $set,
        ];

        // Generate our Token
        $token = ResumptionTokenHelper::generateResumptionToken($verb, $page, $from, $until, $set);

        // Now decode that Token
        $reflection = new ReflectionClass(ResumptionTokenHelper::class);
        $method = $reflection->getMethod('getResumptionTokenParts');
        $method->setAccessible(true);
        $resumptionParts = $method->invoke(null, $token);

        // And check that the Token that was encoded and decoded matches our expected values
        $this->assertEquals(ksort($expectedParts), ksort($resumptionParts));
        // And check that our "get page number" method works as well
        $this->assertEquals(
            $page,
            ResumptionTokenHelper::getPageFromResumptionToken($token, $verb, $from, $until, $set)
        );
        // Check that our "get expiry" method works as well (expecting there to be no value)
        $this->assertNull(ResumptionTokenHelper::getExpiryFromResumptionToken($token));
    }

}
