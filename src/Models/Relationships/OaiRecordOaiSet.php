<?php

namespace Terraformers\OpenArchive\Models\Relationships;

use SilverStripe\ORM\DataObject;
use Terraformers\OpenArchive\Models\OaiRecord;
use Terraformers\OpenArchive\Models\OaiSet;

/**
 * @property int $ParentID
 * @property int $SetID
 * @method OaiRecord Parent()
 * @method OaiSet Set()
 */
class OaiRecordOaiSet extends DataObject
{

    private static string $table_name = 'OaiRecordOaiSet';

    private static array $has_one = [
        'Parent' => OaiRecord::class,
        'Set' => OaiSet::class,
    ];

    private static array $owned_by = [
        'Parent',
    ];

}
