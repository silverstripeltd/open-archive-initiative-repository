<?php

namespace App\Tests\OpenArchive\Models;

use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Models\OaiMember;

class OaiMemberTest extends SapphireTest
{

    protected static $fixture_file = 'OaiMemberTest.yml'; // phpcs:ignore

    public function testFind(): void
    {
        $member = OaiMember::find('Test1', 'Person1');

        $this->assertNotNull($member);
        $this->assertTrue($member->isInDB());

        $member = OaiMember::find('Not', 'Exists');

        $this->assertNull($member);
    }

    public function testFindOrCreate(): void
    {
        // Check that we're set up correctly before we kick off
        $this->assertCount(3, OaiMember::get());

        // Test Member who has both first and surname
        $member = OaiMember::findOrCreate('Test1', 'Person1');

        $this->assertNotNull($member);
        $this->assertTrue($member->isInDB());
        $this->assertEquals('Test1', $member->FirstName);
        $this->assertEquals('Person1', $member->Surname);

        // Test Member who only has first name
        $member = OaiMember::findOrCreate('Test1');

        $this->assertNotNull($member);
        $this->assertTrue($member->isInDB());
        $this->assertEquals('Test1', $member->FirstName);
        $this->assertNull($member->Surname);

        // Test Member who only has surname
        $member = OaiMember::findOrCreate(null, 'Person1');

        $this->assertNotNull($member);
        $this->assertTrue($member->isInDB());
        $this->assertNull($member->FirstName);
        $this->assertEquals('Person1', $member->Surname);

        // Test Member who doesn't exist in our system
        $member = OaiMember::findOrCreate('Not', 'Exists');

        $this->assertNotNull($member);
        $this->assertTrue($member->isInDB());
        $this->assertEquals('Not', $member->FirstName);
        $this->assertEquals('Exists', $member->Surname);

        // There should now be 4 Members in our DB
        $this->assertCount(4, OaiMember::get());
    }

}
