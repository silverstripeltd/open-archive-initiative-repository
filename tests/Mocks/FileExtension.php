<?php

namespace Terraformers\OpenArchive\Tests\Mocks;

use SilverStripe\Assets\File;
use SilverStripe\ORM\DataExtension;
use Terraformers\OpenArchive\Models\OaiRecord;

/**
 * @property File|$this $owner
 */
class FileExtension extends DataExtension
{

    private static array $oai_fields = [
        OaiRecord::FIELD_TITLES => 'Title',
    ];

    /**
     * Testing the extension point in @see OaiRecordUpdateJob
     */
    public function canUpdateOaiRecord(): bool
    {
        // If the Title of the File is anything other than "DeleteMe", then we'll allow the OaiRecord to be updated
        return $this->owner->Title !== 'DeleteMe';
    }

}
