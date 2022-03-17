<?php

namespace Terraformers\OpenArchive\Tests\Models;

use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Models\OaiMember;
use Terraformers\OpenArchive\Models\OaiRecord;
use Terraformers\OpenArchive\Models\OaiSet;

class OaiRecordTest extends SapphireTest
{

    protected static $fixture_file = 'OaiRecordTest.yml'; // phpcs:ignore

    /**
     * Basically just want to test that 1 user can be linked to different records through different relationships
     */
    public function testOaiContributors(): void
    {
        // Testing relationships for our first record
        $record = $this->objFromFixture(OaiRecord::class, 'record1');

        // Test that we have one Contributor
        $this->assertCount(1, $record->OaiContributors());

        /** @var OaiMember $member */
        $member = $record->OaiContributors()->first();

        // Test that the first record has Person1 as a Contributor
        $this->assertEquals('Test1', $member->FirstName);
        $this->assertEquals('Person1', $member->Surname);

        // Test that we have one Creator
        $this->assertCount(1, $record->OaiCreators());

        /** @var OaiMember $member */
        $member = $record->OaiCreators()->first();

        // Test that the first record has Person2 as a Creator
        $this->assertEquals('Test2', $member->FirstName);
        $this->assertEquals('Person2', $member->Surname);

        // Testing relationships for our second record
        $record = $this->objFromFixture(OaiRecord::class, 'record2');

        // Test that we have one Contributor
        $this->assertCount(1, $record->OaiContributors());

        /** @var OaiMember $member */
        $member = $record->OaiContributors()->first();

        // Test that the first record has Person1 as a Contributor
        $this->assertEquals('Test2', $member->FirstName);
        $this->assertEquals('Person2', $member->Surname);

        // Test that we have one Creator
        $this->assertCount(1, $record->OaiCreators());

        /** @var OaiMember $member */
        $member = $record->OaiCreators()->first();

        // Test that the first record has Person2 as a Creator
        $this->assertEquals('Test1', $member->FirstName);
        $this->assertEquals('Person1', $member->Surname);
    }

    public function testAddContributor(): void
    {
        $record = $this->objFromFixture(OaiRecord::class, 'record3');

        // Check that we're set up correctly with just 2 OaiMembers in our DB
        $this->assertCount(2, OaiMember::get());
        // And no Contributors assigned
        $this->assertCount(0, $record->OaiContributors());

        // We'll now start adding Contributors

        // This Member should already exist in our DB
        $record->addContributor('Test1', 'Person1');

        // This Member does not exist in our DB, so should be created
        $record->addContributor('New', 'Contributor');

        // Start checking
        $this->assertCount(2, $record->OaiContributors());
        $this->assertListEquals(
            [
                [
                    'FirstName' => 'Test1',
                    'Surname' => 'Person1',
                ],
                [
                    'FirstName' => 'New',
                    'Surname' => 'Contributor',
                ],
            ],
            $record->OaiContributors()
        );
    }

    public function testAddCreator(): void
    {
        $record = $this->objFromFixture(OaiRecord::class, 'record3');

        // Check that we're set up correctly with just 2 OaiMembers in our DB
        $this->assertCount(2, OaiMember::get());
        // And no Creators assigned
        $this->assertCount(0, $record->OaiCreators());

        // We'll now start adding Contributors

        // This Member should already exist in our DB
        $record->addCreator('Test1', 'Person1');

        // This Member does not exist in our DB, so should be created
        $record->addCreator('New', 'Creator');

        // Start checking
        $this->assertCount(2, $record->OaiCreators());
        $this->assertListEquals(
            [
                [
                    'FirstName' => 'Test1',
                    'Surname' => 'Person1',
                ],
                [
                    'FirstName' => 'New',
                    'Surname' => 'Creator',
                ],
            ],
            $record->OaiCreators()
        );
    }

    public function testRemoveContributor(): void
    {
        $record = $this->objFromFixture(OaiRecord::class, 'record1');

        // Check that we're set up correctly before we kick off
        $this->assertCount(1, $record->OaiContributors());

        // This should do nothing
        $record->removeContributor('Not', 'Exists');

        // No changes should have happened
        $this->assertCount(1, $record->OaiContributors());

        // This matches the first name of our Member, but this shouldn't match since the Surname is missing
        $record->removeContributor('Test1');

        // No changes should have happened
        $this->assertCount(1, $record->OaiContributors());

        // Now it should match and remove the Member
        $record->removeContributor('Test1', 'Person1');

        // No changes should have happened
        $this->assertCount(0, $record->OaiContributors());
    }

    public function testRemoveCreator(): void
    {
        $record = $this->objFromFixture(OaiRecord::class, 'record1');

        // Check that we're set up correctly before we kick off
        $this->assertCount(1, $record->OaiCreators());

        // This should do nothing
        $record->removeCreator('Not', 'Exists');

        // No changes should have happened
        $this->assertCount(1, $record->OaiCreators());

        // This matches the first name of our Member, but this shouldn't match since the Surname is missing
        $record->removeCreator('Test2');

        // No changes should have happened
        $this->assertCount(1, $record->OaiCreators());

        // Now it should match and remove the Member
        $record->removeCreator('Test2', 'Person2');

        // No changes should have happened
        $this->assertCount(0, $record->OaiCreators());
    }

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

}
