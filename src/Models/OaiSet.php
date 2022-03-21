<?php

namespace Terraformers\OpenArchive\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyThroughList;
use Terraformers\OpenArchive\Models\Relationships\OaiRecordOaiContributor;
use Terraformers\OpenArchive\Models\Relationships\OaiRecordOaiSet;

/**
 * @property string $Title
 * @method HasManyList|OaiRecordOaiContributor[] OaiRecordOaiContributors()
 * @method ManyManyThroughList|OaiRecord[] ContributorOaiRecords()
 */
class OaiSet extends DataObject
{

    private static string $table_name = 'OaiSet';

    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    private static array $has_many = [
        'OaiRecordOaiSets' => OaiRecordOaiSet::class . '.Set',
    ];

    private static array $many_many = [
        'OaiRecords' => [
            'through' => OaiRecordOaiSet::class,
            'from' => 'Set',
            'to' => 'Parent',
        ],
    ];

    public static function find(string $title): ?OaiSet
    {
        /** @var OaiSet $set */
        $set = static::get()
            ->filter('Title', $title)
            ->first();

        return $set;
    }

    public static function findOrCreate(string $title): OaiSet
    {
        $set = static::find($title);

        if (!$set || !$set->exists()) {
            $set = static::create();
            $set->Title = $title;

            $set->write();
        }

        return $set;
    }

}
