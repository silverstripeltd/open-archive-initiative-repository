<?php

namespace Terraformers\OpenArchive\Tests\Models;

use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Models\OaiRecord;
use Terraformers\OpenArchive\Models\OaiSet;

class OaiRecordTest extends SapphireTest
{

    protected static $fixture_file = 'OaiRecordTest.yml'; // phpcs:ignore

    public function testAddSet(): void
    {
        $record = $this->objFromFixture(OaiRecord::class, 'record1');

        // Check that we're set up correctly with just 2 OaiMembers in our DB
        $this->assertCount(2, OaiSet::get());
        // And 1 Set already assigned
        $this->assertCount(1, $record->OaiSets());

        // We'll now start adding Sets

        // This Set should already exist in our DB
        $record->addSet('Set2');

        // This Set does not exist in our DB, so should be created
        $record->addSet('NewSet');

        // Start checking
        $this->assertCount(3, $record->OaiSets());
        $this->assertListEquals(
            [
                [
                    'Title' => 'Set1',
                ],
                [
                    'Title' => 'Set2',
                ],
                [
                    'Title' => 'NewSet',
                ],
            ],
            $record->OaiSets()
        );
    }

    public function testRemoveSet(): void
    {
        $record = $this->objFromFixture(OaiRecord::class, 'record1');

        // Check that we're set up correctly before we kick off
        $this->assertCount(1, $record->OaiSets());

        // This should do nothing
        $record->removeSet('NotExists');

        // No changes should have happened
        $this->assertCount(1, $record->OaiSets());

        // Now it should match and remove the Set
        $record->removeSet('Set1');

        // No changes should have happened
        $this->assertCount(0, $record->OaiSets());
    }

    public function testFieldSupportsCsv(): void
    {
        foreach (OaiRecord::MANAGED_FIELDS as $fieldName) {
            $this->assertTrue(OaiRecord::fieldSupportsCsv($fieldName));
        }
    }

}
