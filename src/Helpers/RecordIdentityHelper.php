<?php

namespace Terraformers\OpenArchive\Helpers;

use SilverStripe\Control\Director;
use Terraformers\OpenArchive\Models\OaiRecord;

class RecordIdentityHelper
{

    public static function generateOaiIdentifier(OaiRecord $oaiRecord): string
    {
        return sprintf('oai:%s:%s', Director::host(), $oaiRecord->ID);
    }

    public static function getIdFromOaiIdentifier(string $oaiIdentifier): ?int
    {
        $parts = explode(':', $oaiIdentifier);

        if (!$parts) {
            return null;
        }

        // ID should always be the last element in the array
        return (int) end($parts);
    }

}
