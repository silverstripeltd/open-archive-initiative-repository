<?php

namespace Terraformers\OpenArchive\Jobs;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Terraformers\OpenArchive\Models\OaiRecord;

/**
 * Note: Multiple actions could have been triggered on the DataObject (including archive/restore/etc) before this Job
 * processes. That is totally fine. The OaiRecord will represent whatever the state of the DataObject is at the point
 * in time when this Job is actually *processed* (as apposed to, the point in time when the Job was queued).
 *
 * @property string $className
 * @property int $id
 */
class OaiRecordUpdateJob extends AbstractQueuedJob implements QueuedJob
{

    use Injectable;

    public function hydrate(string $className, int $id): void
    {
        $this->className = $className;
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return 'Update OAI Record';
    }

    public function getJobType(): int
    {
        return QueuedJob::QUEUED;
    }

    public function process(): void
    {
        // Fetch our DataObject from a LIVE context. This covers us for when Versioned DataObjects have been unpublished
        // but not archived. From the p.o.v. of our OAI feed, unpublished is still a "Deleted" state
        $dataObject = Versioned::withVersionedMode(function (): ?DataObject {
            Versioned::set_stage(Versioned::LIVE);

            return DataObject::get($this->className)->byID($this->id);
        });

        // The DataObject is not currently available in a LIVE context, so the corresponding OaiRecord should be marked
        // as deleted
        if (!$dataObject || !$dataObject->exists()) {
            $this->markOaiRecordDeleted();
            $this->isComplete = true;

            return;
        }

        $this->updateOaiRecord($dataObject);
        $this->isComplete = true;
    }

    protected function updateOaiRecord(DataObject $dataObject): void
    {
        $oaiRecord = $this->getOaiRecord();

        // If there is no existing OaiRecord then we need to create one
        if (!$oaiRecord || !$oaiRecord->exists()) {
            $oaiRecord = OaiRecord::create();
            $oaiRecord->RecordClass = $this->className;
            $oaiRecord->RecordID = $this->id;
        }

        $oaiFields = $dataObject->config()->get('oai_fields');

        foreach ($oaiFields as $oaiField => $dataObjectField) {
            $oaiRecord->{$oaiField} = $dataObject->relField($dataObjectField);
        }

        // This record could have been previously deleted and now restored
        $oaiRecord->Deleted = 0;
        $oaiRecord->write();
    }

    protected function markOaiRecordDeleted(): void
    {
        $oaiRecord = $this->getOaiRecord();

        // If there is no existing OaiRecord, then there is nothing for us to mark as deleted
        if (!$oaiRecord || !$oaiRecord->exists()) {
            return;
        }

        $oaiRecord->Deleted = 1;
        $oaiRecord->write();
    }

    protected function getOaiRecord(): ?OaiRecord
    {
        // There should only ever be one OaiRecord per DataObject, but now is a chance for us to clean up if that was
        // ever not the case (for whatever reason)
        $oaiRecords = OaiRecord::get()->filter([
            'RecordClass' => $this->className,
            'RecordID' => $this->id,
        ]);

        // There are no matching OaiRecords
        if ($oaiRecords->count() === 0) {
            return null;
        }

        // There is only 1, so we're good to just return that
        if ($oaiRecords->count() === 1) {
            /** @var OaiRecord $oaiRecord */
            $oaiRecord = $oaiRecords->first();

            return $oaiRecord;
        }

        // Somehow we've ended up in a state where we have multiple OaiRecords for one DataObject. This is our chance
        // to clean up. We'll grab the last() record (as that should be the one most recently created), and we'll
        // delete the rest
        /** @var OaiRecord $oaiRecord */
        $oaiRecord = $oaiRecords->last();
        /** @var DataList|OaiRecord[] $extraOaiRecords */
        $extraOaiRecords = $oaiRecords->exclude('ID', $oaiRecord->ID);

        foreach ($extraOaiRecords as $extraOaiRecord) {
            $extraOaiRecord->delete();
        }

        return $oaiRecord;
    }

}
