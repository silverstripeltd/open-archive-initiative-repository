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

    public function onAfterPublish(): void
    {
        $this->triggerUpdate();
    }

    /**
     * Versioned DataObjects can technically still be delete()ed without that going through any unpublish process. So..
     * We have to cover both onBeforeDelete() (already covered by OaiRecordManager) and onBeforeUnpublish(). This isn't
     * too bad though, since QueuedJobs already stops itself from queueing duplicate jobs
     */
    public function onBeforeUnpublish(): void
    {
        $this->triggerUpdate();
    }

}
