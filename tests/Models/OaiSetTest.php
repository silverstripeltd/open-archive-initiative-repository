<?php

namespace Terraformers\OpenArchive\Tests\Models;

use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Models\OaiSet;

class OaiSetTest extends SapphireTest
{

    protected static $fixture_file = 'OaiSetTest.yml'; // phpcs:ignore

    public function testFind(): void
    {
        $member = OaiSet::find('Set1');

        $this->assertNotNull($member);
        $this->assertTrue($member->isInDB());

        $member = OaiSet::find('SetFail');

        $this->assertNull($member);
    }

    public function testFindOrCreate(): void
    {
        // Check that we're set up correctly before we kick off
        $this->assertCount(1, OaiSet::get());

        // Test Set exists
        $set = OaiSet::findOrCreate('Set1');

        $this->assertNotNull($set);
        $this->assertTrue($set->isInDB());
        $this->assertEquals('Set1', $set->Title);

        // Test Set that doesn't exist in our system. It should get created
        $set = OaiSet::findOrCreate('NotExists');

        $this->assertNotNull($set);
        $this->assertTrue($set->isInDB());
        $this->assertEquals('NotExists', $set->Title);

        // There should now be 2 Sets in our DB
        $this->assertCount(2, OaiSet::get());
    }

}
