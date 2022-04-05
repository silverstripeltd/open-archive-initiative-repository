<?php

namespace Terraformers\OpenArchive\Tests\Helpers;

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

        $metadataPrefix = 'oai_dc';
        $page = 3;
        $from = '2022-01-01T01:00:00Z';
        $until = '2022-01-01T02:00:00Z';
        $set = 2;
        // -13 for UTC+0, but +1 for the duration of the expiry
        $expiry = '2020-01-01T01:00:00Z';

        $expectedParts = [
            'metadataPrefix' => $metadataPrefix,
            'page' => $page,
            'from' => $from,
            'until' => $until,
            'set' => $set,
            'expiry' => $expiry,
        ];
        ksort($expectedParts);

        // Generate our Token
        $token = ResumptionTokenHelper::generateResumptionToken($metadataPrefix, $page, $from, $until, $set);

        // Just a simple check that our Token is a string, not going to bother matching hashes
        $this->assertIsString($token);

        $parts = ResumptionTokenHelper::getRequestParamsFromResumptionToken($token);

        // Check that the Token that was encoded and decoded matches our expected values
        $this->assertEquals($expectedParts, $parts);
        // Check that our "get expiry" method works as well
        $this->assertEquals($expiry, ResumptionTokenHelper::getExpiryFromResumptionToken($token));
    }

    public function testResumptionTokenHasExpired(): void
    {
        // We'll use Auckland time for our tests
        date_default_timezone_set('Pacific/Auckland');

        $this->expectExceptionMessage('Invalid resumption token');

        DBDatetime::set_mock_now('2020-01-01 13:00:00');

        $metadataPrefix = 'oai_dc';
        $page = 3;
        $from = '2022-01-01T01:00:00Z';
        $until = '2022-01-01T02:00:00Z';
        $set = 2;

        // Generate our Token
        $token = ResumptionTokenHelper::generateResumptionToken($metadataPrefix, $page, $from, $until, $set);

        // Now set the time to a couple hours later. This should invalidate the Resumption Token
        DBDatetime::set_mock_now('2020-01-01 15:00:00');

        // This should throw an Exception
        ResumptionTokenHelper::getRequestParamsFromResumptionToken($token);
    }

    public function testResumptionTokenNoExpiry(): void
    {
        OaiController::config()->set('resumption_token_expiry', null);

        $metadataPrefix = 'oai_dc';
        $page = 3;
        $from = '2022-01-01T01:00:00Z';
        $until = '2022-01-01T02:00:00Z';
        $set = 2;

        $expectedParts = [
            'metadataPrefix' => $metadataPrefix,
            'page' => $page,
            'from' => $from,
            'until' => $until,
            'set' => $set,
        ];
        ksort($expectedParts);

        // Generate our Token
        $token = ResumptionTokenHelper::generateResumptionToken($metadataPrefix, $page, $from, $until, $set);

        // Just a simple check that our Token is a string, not going to bother matching hashes
        $this->assertIsString($token);

        $parts = ResumptionTokenHelper::getRequestParamsFromResumptionToken($token);

        // Check that the Token that was encoded and decoded matches our expected values
        $this->assertEquals($expectedParts, $parts);
        // Check that our "get expiry" method works as well (expecting there to be no value)
        $this->assertNull(ResumptionTokenHelper::getExpiryFromResumptionToken($token));
    }

}
