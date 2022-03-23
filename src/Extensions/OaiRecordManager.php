<?php

namespace Terraformers\OpenArchive\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Terraformers\OpenArchive\Jobs\OaiRecordUpdateJob;
use Terraformers\OpenArchive\Models\OaiRecord;

/**
 * @property DataObject|$this $owner
 * @method HasManyList|OaiRecord[] OaiRecords()
 */
class OaiRecordManager extends DataExtension
{

    private static array $has_many = [
        'OaiRecords' => OaiRecord::class,
    ];

    private static array $oai_fields = [];

    public function onAfterWrite(): void
    {
        $this->triggerOaiRecordUpdate();
    }

    public function onBeforeDelete(): void
    {
        $this->triggerOaiRecordUpdate();
    }

    /**
     * Why Queued Jobs to trigger updates?
     * - Performance: This module can't know what mappings you may have created, and some of them could end up
     *   negatively affecting the performance of write/publish actions. By moving the management of Record data into
     *   a Job, we make it asynchronous, and it doesn't affect author experiences.
     * - Data integrity: The Update action involves the fetching of existing OaiRecords and the creation of new ones
     *   (when they don't already exist). If this action was synchronous and multiple Updates for the same record were
     *   triggered at the same time, there is a chance (albeit small) that multiple OaiRecords end up being created. By
     *   moving this action to an asynchronous Job, we significantly reduce the likelihood of duplicate OaiRecords
     */
    public function triggerOaiRecordUpdate(): void
    {
        $job = OaiRecordUpdateJob::create();
        $job->hydrate($this->owner->ClassName, $this->owner->ID);

        QueuedJobService::singleton()->queueJob($job);
    }

}
