<?php

namespace App\Tests\OpenArchive\Mocks;

use Terraformers\OpenArchive\Extensions\OaiRecordManager;
use SilverStripe\ORM\DataObject;

/**
 * @property string $Title
 * @mixin OaiRecordManager
 */
class FakeDataObject extends DataObject
{

    private static string $table_name = 'FakeDataObject';

    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    private static array $extensions = [
        OaiRecordManager::class,
    ];

}
