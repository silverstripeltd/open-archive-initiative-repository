<?php

namespace Terraformers\OpenArchive\Tests\Mocks;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Terraformers\OpenArchive\Extensions\VersionedOaiRecordManager;

/**
 * @property string $Title
 * @mixin VersionedOaiRecordManager
 * @mixin Versioned
 */
class VersionedFakeDataObject extends DataObject
{

    private static string $table_name = 'VersionedFakeDataObject';

    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    private static array $extensions = [
        Versioned::class,
        VersionedOaiRecordManager::class,
    ];

}
