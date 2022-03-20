<?php

namespace Terraformers\OpenArchive\Tests\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Terraformers\OpenArchive\Jobs\OaiRecordUpdateJob;
use Terraformers\OpenArchive\Tests\Mocks\VersionedFakeDataObject;

class VersionedOaiRecordManagerTest extends SapphireTest
{

    protected static $fixture_file = 'VersionedOaiRecordManagerTest.yml'; // phpcs:ignore

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     * @var array
     */
    protected static $extra_dataobjects = [
        VersionedFakeDataObject::class,
    ];

    /**
     * @dataProvider triggerActionProvider
     */
    public function testTriggerAction(string $fixtureId, string $action): void
    {
        $dataObject = $this->objFromFixture(VersionedFakeDataObject::class, $fixtureId);
        // Make sure our record is published to kick us off
        $dataObject->publishSingle();

        // Make sure we're set up correctly with no existing QueuedJobs
        DB::query('TRUNCATE TABLE QueuedJobDescriptor;');
        $this->assertCount(0, QueuedJobDescriptor::get());

        // Then we'll make some edits
        $dataObject->Title = 'Object1Edit';
        $id = $dataObject->ID;

        // This should NOT trigger a Job to be queued (because the model is Versioned)
        $dataObject->write();

        // Should still be zero
        $this->assertCount(0, QueuedJobDescriptor::get());

        // This should trigger a Job to be queued
        $dataObject->{$action}();

        $queuedJobs = QueuedJobDescriptor::get()->filter('Implementation', OaiRecordUpdateJob::class);

        $this->assertCount(1, $queuedJobs);

        $queuedJob = $queuedJobs->first();

        $this->assertStringContainsString(VersionedFakeDataObject::class, $queuedJob->SavedJobData);
        $this->assertStringContainsString(sprintf('"id";i:%s;', $id), $queuedJob->SavedJobData);
    }

    public function testOnAfterWrite(): void
    {
        // Double check that we're set up correctly with no Jobs currently queued
        DB::query('TRUNCATE TABLE QueuedJobDescriptor;');
        $this->assertCount(0, QueuedJobDescriptor::get());

        // Kick things off
        $dataObject = $this->objFromFixture(VersionedFakeDataObject::class, 'object6');
        $dataObject->Title = 'Object1Edit';

        // This should NOT trigger a Job to be queued (because the model is Versioned)
        $dataObject->write();

        // Should still be zero
        $this->assertCount(0, QueuedJobDescriptor::get());
    }

    public function triggerActionProvider(): array
    {
        return [
            ['object1', 'publishRecursive'],
            ['object2', 'publishSingle'],
            ['object3', 'delete'],
            ['object4', 'doUnpublish'],
            ['object5', 'doArchive'],
        ];
    }

    protected function setUp(): void
    {
        // This needs to come before the parent::setUp(). This is here to stop QueuedJobService from attempting to run
        // calls on the DB after shutdown (it results in errors being thrown that have nothing to do with your test)
        Config::modify()->set(QueuedJobService::class, 'use_shutdown_function', false);

        parent::setUp();
    }

}
