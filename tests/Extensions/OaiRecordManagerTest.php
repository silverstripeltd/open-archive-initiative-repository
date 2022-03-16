<?php

namespace App\Tests\OpenArchive\Extensions;

use App\Tests\OpenArchive\Mocks\FakeDataObject;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Terraformers\OpenArchive\Jobs\OaiRecordUpdateJob;

class OaiRecordManagerTest extends SapphireTest
{

    protected static $fixture_file = 'OaiRecordManagerTest.yml'; // phpcs:ignore

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     * @var array
     */
    protected static $extra_dataobjects = [
        FakeDataObject::class,
    ];

    /**
     * @dataProvider triggerActionProvider
     */
    public function testTriggerAction(string $fixtureId, string $action): void
    {
        // Double check that we're set up correctly with no Jobs currently queued
        $this->assertCount(0, QueuedJobDescriptor::get());

        // Kick things off
        $dataObject = $this->objFromFixture(FakeDataObject::class, $fixtureId);
        $dataObject->Title = 'Object1Edit';
        $id = $dataObject->ID;

        // This should trigger a Job to be queued
        $dataObject->{$action}();

        $queuedJobs = QueuedJobDescriptor::get()->filter('Implementation', OaiRecordUpdateJob::class);

        $this->assertCount(1, $queuedJobs);

        $queuedJob = $queuedJobs->first();

        $this->assertStringContainsString(FakeDataObject::class, $queuedJob->SavedJobData);
        $this->assertStringContainsString(sprintf('"id";i:%s;', $id), $queuedJob->SavedJobData);
    }

    public function triggerActionProvider(): array
    {
        return [
            ['object1', 'write'],
            ['object2', 'delete'],
        ];
    }

    protected function setUp(): void
    {
        // This needs to come before the parent::setUp(). This is here to stop QueuedJobService from attempting to run
        // calls on the DB after shutdown (it results in errors being thrown that have nothing to do with your test)
        Config::modify()->set(QueuedJobService::class, 'use_shutdown_function', false);

        parent::setUp();

        // The creation of DataObjects during the setUp() step will have queued some Jobs. We just want to clear them
        // all out before we start our actual tests
        DB::query('TRUNCATE TABLE QueuedJobDescriptor;');
    }

}
