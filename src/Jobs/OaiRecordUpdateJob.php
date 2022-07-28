<?php

namespace Terraformers\OpenArchive\Jobs;

use Exception;
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
        /** @var DataObject $dataObject */
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

        // Extension point for DataObjects to determine whether they should be able to update OaiRecords. This should
        // have been triggered during the OaiRecordManager::triggerOaiRecordUpdate() step, however, something could
        // have happened to the DataObject since then, so we'll recheck now
        if (in_array(false, $dataObject->invokeWithExtensions('canUpdateOaiRecord'), true)) {
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

        // Start processing our managed fields
        $oaiFields = $dataObject->config()->get('oai_fields');
        $errors = [];

        foreach ($oaiFields as $oaiField => $dataObjectField) {
            if (!in_array($oaiField, OaiRecord::MANAGED_FIELDS, true)) {
                // Don't throw immediately. Continue processing in case there are other errors that we want to tell the
                // dev/s about
                $errors[] = sprintf('Unsupported OAI field provided: %s', $oaiField);
            }

            $oaiRecord->{$oaiField} = $this->sanitiseContent($dataObject->relField($dataObjectField));
        }

        if ($errors) {
            throw new Exception(implode("\r\n", $errors));
        }

        // The date field is not a managed field, and should always be the LastEdited date of our DataObject
        $oaiRecord->Date = $dataObject->LastEdited;
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

    /**
     * @param mixed $content
     * @return mixed
     */
    protected function sanitiseContent($content)
    {
        if (!is_string($content)) {
            return $content;
        }

        // Attempt to remove any illegal characters
        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $content);
    }

}
