<?php

namespace Terraformers\OpenArchive\Tests\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Terraformers\OpenArchive\Jobs\OaiRecordUpdateJob;
use Terraformers\OpenArchive\Tests\Mocks\FakeDataObject;
use Terraformers\OpenArchive\Tests\Mocks\FakeDataObjectNoUpdate;

class OaiRecordManagerTest extends SapphireTest
{

    protected static $fixture_file = 'OaiRecordManagerTest.yml'; // phpcs:ignore

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     * @var array
     */
    protected static $extra_dataobjects = [
        FakeDataObject::class,
        FakeDataObjectNoUpdate::class,
    ];

    /**
     * @dataProvider triggerOaiRecordUpdate
     */
    public function testTriggerOaiRecordUpdate(string $fixtureId, string $action): void
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

    public function testNoTriggerOaiRecordUpdate(): void
    {
        // Double check that we're set up correctly with no Jobs currently queued
        $this->assertCount(0, QueuedJobDescriptor::get());

        // Kick things off
        $dataObject = $this->objFromFixture(FakeDataObjectNoUpdate::class, 'object1');
        $dataObject->Title = 'Object1Edit';

        // This should trigger a Job to be queued
        $dataObject->write();

        // There should still be no jobs, as this DataObject said it can't update OaiRecords
        $this->assertCount(0, QueuedJobDescriptor::get());
    }

    public function triggerOaiRecordUpdate(): array
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
