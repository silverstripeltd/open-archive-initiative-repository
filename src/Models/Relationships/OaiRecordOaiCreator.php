<?php

namespace Terraformers\OpenArchive\Models\Relationships;

use Terraformers\OpenArchive\Models\OaiMember;
use Terraformers\OpenArchive\Models\OaiRecord;
use SilverStripe\ORM\DataObject;

/**
 * @property int $ParentID
 * @property int $CreatorID
 * @method OaiRecord Parent()
 * @method OaiMember Creator()
 */
class OaiRecordOaiCreator extends DataObject
{

    private static string $table_name = 'OaiRecordOaiCreator';

    private static array $has_one = [
        'Parent' => OaiRecord::class,
        'Creator' => OaiMember::class,
    ];

    private static array $owned_by = [
        'Parent',
    ];

}
