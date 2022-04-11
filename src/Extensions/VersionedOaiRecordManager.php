<?php

namespace Terraformers\OpenArchive\Extensions;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * @property DataObject|Versioned|$this $owner
 */
class VersionedOaiRecordManager extends OaiRecordManager
{

    public function onAfterWrite(): void
    {
        // For Versioned DataObjects, we want to override the parent methods so that onBeforeWrite does *not* update our
        // OaiRecord - we now expect this to happen onAfterPublish
    }

    /**
     * This catches publishSingle() when/if there are changes to that particular model. Note: Silverstripe does not
     * actually trigger publishing for a model that has not changed
     */
    public function onAfterPublish(): void
    {
        $this->triggerOaiRecordUpdate();
    }

    /**
     * This catches any calls to publishRecursive(), and is triggered on the owner regardless of whether that owner
     * had changes or not (see above onAfterPublish() note). This does mean a duplication of effort if the owner
     * also triggered our onAfterPublish(), but this is fine since QueuedJobs already stops itself from queueing
     * duplicate jobs
     */
    public function onAfterPublishRecursive(): void
    {
        $this->triggerOaiRecordUpdate();
    }

    /**
     * Versioned DataObjects can technically still be delete()ed without that going through any unpublish process. So..
     * We have to cover both onBeforeDelete() (already covered by OaiRecordManager) and onBeforeUnpublish(). This is
     * fine though, since QueuedJobs already stops itself from queueing duplicate jobs
     */
    public function onBeforeUnpublish(): void
    {
        $this->triggerOaiRecordUpdate();
    }

}
