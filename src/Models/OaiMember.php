<?php

namespace Terraformers\OpenArchive\Models;

use Terraformers\OpenArchive\Models\Relationships\OaiRecordOaiContributor;
use Terraformers\OpenArchive\Models\Relationships\OaiRecordOaiCreator;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyThroughList;

/**
 * @property string $FirstName
 * @property string $Surname
 * @method HasManyList|OaiRecordOaiContributor[] OaiRecordOaiContributors()
 * @method HasManyList|OaiRecordOaiCreator[] OaiRecordOaiCreators()
 * @method ManyManyThroughList|OaiRecord[] ContributorOaiRecords()
 * @method ManyManyThroughList|OaiRecord[] CreatorOaiRecords()
 */
class OaiMember extends DataObject
{

    private static string $table_name = 'OaiMember';

    private static array $db = [
        'FirstName' => 'Varchar(255)',
        'Surname' => 'Varchar(255)',
    ];

    private static array $has_many = [
        'OaiRecordOaiContributors' => OaiRecordOaiContributor::class . '.Contributor',
        'OaiRecordOaiCreators' => OaiRecordOaiCreator::class . '.Creator',
    ];

    private static array $many_many = [
        'ContributorOaiRecords' => [
            'through' => OaiRecordOaiContributor::class,
            'from' => 'Contributor',
            'to' => 'Parent',
        ],
        'CreatorOaiRecords' => [
            'through' => OaiRecordOaiCreator::class,
            'from' => 'Creator',
            'to' => 'Parent',
        ],
    ];

    public static function find(?string $firstName = null, ?string $surname = null): ?OaiMember
    {
        /** @var OaiMember $member */
        $member = static::get()
            ->filter([
                'FirstName' => $firstName,
                'Surname' => $surname,
            ])
            ->first();

        return $member;
    }

    public static function findOrCreate(?string $firstName = null, ?string $surname = null): OaiMember
    {
        /** @var OaiMember $member */
        $member = static::find($firstName, $surname);

        if (!$member || !$member->exists()) {
            $member = static::create();
            $member->FirstName = $firstName;
            $member->Surname = $surname;

            $member->write();
        }

        return $member;
    }

}
