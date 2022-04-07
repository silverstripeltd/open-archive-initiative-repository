<?php

namespace Terraformers\OpenArchive\Tests\Helpers;

use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Helpers\RecordIdentityHelper;
use Terraformers\OpenArchive\Models\OaiRecord;

class RecordIdentityHelperTest extends SapphireTest
{

    protected static $fixture_file = 'RecordIdentityHelperTest.yml'; // phpcs:ignore

    public function testGenerateOaiIdentifier(): void
    {
        $host = Director::host();
        $record = $this->objFromFixture(OaiRecord::class, 'record1');

        $this->assertEquals(
            sprintf('oai:%s:%s', $host, $record->ID),
            RecordIdentityHelper::generateOaiIdentifier($record)
        );
    }

    public function testGetIdFromOaiIdentifier(): void
    {
        $host = Director::host();
        $record = $this->objFromFixture(OaiRecord::class, 'record1');
        $oaiIdentifier = sprintf('oai:%s:%s', $host, $record->ID);

        $this->assertEquals($record->ID, RecordIdentityHelper::getIdFromOaiIdentifier($oaiIdentifier));
    }

}
