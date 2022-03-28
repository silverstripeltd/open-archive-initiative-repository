<?php

namespace Terraformers\OpenArchive\Tasks;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Terraformers\OpenArchive\Jobs\OaiRecordUpdateJob;
use Terraformers\OpenArchive\Models\OaiRecord;

class UpdateOaiRecords extends BuildTask
{

    private static $segment = 'update-oai-records'; // phpcs:ignore

    protected $title = 'Update OAI Records'; // phpcs:ignore

    protected $description = 'Queue update jobs for all existing OAI Records'; // phpcs:ignore

    /**
     * @param HTTPRequest $request
     */
    public function run($request) // phpcs:ignore
    {
        /** @var DataList|OaiRecord[] $oaiRecords */
        $oaiRecords = OaiRecord::get();

        foreach ($oaiRecords as $oaiRecord) {
            $job = OaiRecordUpdateJob::create();
            $job->hydrate($oaiRecord->RecordClass, $oaiRecord->RecordID);

            QueuedJobService::singleton()->queueJob($job);
        }

        echo 'Jobs queued to update all existing OAI Records';
    }

}
