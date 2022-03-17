<?php

namespace Terraformers\OpenArchive\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyThroughList;
use Terraformers\OpenArchive\Models\Relationships\OaiRecordOaiContributor;
use Terraformers\OpenArchive\Models\Relationships\OaiRecordOaiCreator;
use Terraformers\OpenArchive\Models\Relationships\OaiRecordOaiSet;

/**
 * @property string $Coverage
 * @property string $Description
 * @property int $Deleted
 * @property string $Format
 * @property string $Language
 * @property string $Publisher
 * @property string $RecordClass
 * @property int $RecordID
 * @property string $Relation
 * @property string $Rights
 * @property string $Source
 * @property string $Subjects CSV of subjects. Separator = ",", Enclosure = '"', Escape = "\\"
 * @property string $Title
 * @property string $Type
 * @method HasManyList|OaiRecordOaiContributor[] OaiRecordOaiContributors()
 * @method HasManyList|OaiRecordOaiCreator[] OaiRecordOaiCreators()
 * @method HasManyList|OaiRecordOaiSet[] OaiRecordOaiSets()
 * @method ManyManyThroughList|OaiMember[] OaiContributors()
 * @method ManyManyThroughList|OaiMember[] OaiCreators()
 * @method ManyManyThroughList|OaiSet[] OaiSets()
 */
class OaiRecord extends DataObject
{

    public const FIELD_COVERAGE = 'Coverage';
    public const FIELD_DESCRIPTION = 'Description';
    public const FIELD_DELETED = 'Deleted';
    public const FIELD_FORMAT = 'Format';
    public const FIELD_LANGUAGE = 'Language';
    public const FIELD_PUBLISHER = 'Publisher';
    public const FIELD_RELATION = 'Relation';
    public const FIELD_RIGHTS = 'Rights';
    public const FIELD_SOURCE = 'Source';
    public const FIELD_SUBJECTS = 'Subjects';
    public const FIELD_TITLE = 'Title';
    public const FIELD_TYPE = 'Type';

    public const MANAGED_FIELDS = [
        self::FIELD_COVERAGE,
        self::FIELD_DESCRIPTION,
        self::FIELD_FORMAT,
        self::FIELD_LANGUAGE,
        self::FIELD_PUBLISHER,
        self::FIELD_RELATION,
        self::FIELD_RIGHTS,
        self::FIELD_SOURCE,
        self::FIELD_SUBJECTS,
        self::FIELD_TITLE,
        self::FIELD_TYPE,
    ];

    private static string $table_name = 'OaiRecord';

    private static array $db = [
        self::FIELD_COVERAGE => 'Varchar(255)',
        self::FIELD_DESCRIPTION => 'Varchar(255)',
        self::FIELD_DELETED => 'Boolean(0)',
        self::FIELD_FORMAT => 'Varchar(255)',
        self::FIELD_LANGUAGE => 'Varchar(255)',
        self::FIELD_PUBLISHER => 'Varchar(255)',
        self::FIELD_RELATION => 'Varchar(255)',
        self::FIELD_RIGHTS => 'Varchar(255)',
        self::FIELD_SOURCE => 'Varchar(255)',
        self::FIELD_SUBJECTS => 'Text',
        self::FIELD_TITLE => 'Varchar(255)',
        self::FIELD_TYPE => 'Varchar(255)',
    ];

    private static array $has_one = [
        'Record' => DataObject::class,
    ];

    private static array $has_many = [
        'OaiRecordOaiContributors' => OaiRecordOaiContributor::class . '.Parent',
        'OaiRecordOaiCreators' => OaiRecordOaiCreator::class . '.Parent',
        'OaiRecordOaiSets' => OaiRecordOaiSet::class . '.Parent',
    ];

    private static array $many_many = [
        'OaiContributors' => [
            'through' => OaiRecordOaiContributor::class,
            'from' => 'Parent',
            'to' => 'Contributor',
        ],
        'OaiCreators' => [
            'through' => OaiRecordOaiCreator::class,
            'from' => 'Parent',
            'to' => 'Creator',
        ],
        'OaiSets' => [
            'through' => OaiRecordOaiSet::class,
            'from' => 'Parent',
            'to' => 'Set',
        ],
    ];

    public function addContributor(?string $firstName = null, ?string $surname = null): void
    {
        $this->OaiContributors()->add(OaiMember::findOrCreate($firstName, $surname));
    }

    public function addCreator(?string $firstName = null, ?string $surname = null): void
    {
        $this->OaiCreators()->add(OaiMember::findOrCreate($firstName, $surname));
    }

    public function addSet(string $title): void
    {
        $this->OaiSets()->add(OaiSet::findOrCreate($title));
    }

    public function removeContributor(?string $firstName = null, ?string $surname = null): void
    {
        $this->removeFilteredFromList(
            $this->OaiContributors(),
            [
                'FirstName' => $firstName,
                'Surname' => $surname,
            ]
        );
    }

    public function removeCreator(?string $firstName = null, ?string $surname = null): void
    {
        $this->removeFilteredFromList(
            $this->OaiCreators(),
            [
                'FirstName' => $firstName,
                'Surname' => $surname,
            ]
        );
    }

    public function removeSet(string $title): void
    {
        $this->removeFilteredFromList($this->OaiSets(), ['Title' => $title]);
    }

    protected function removeFilteredFromList(ManyManyThroughList $list, array $filters): void
    {
        // There should only ever be 1 of each, but this is a chance to tidy up if (for whatever reason) we ended up
        // with multiple
        $records = $list->filter($filters);

        // Nothing for us to do here if there are no matching records
        if ($records->count() === 0) {
            return;
        }

        // Remove all the records that we found
        foreach ($records->column() as $id) {
            $list->removeByID($id);
        }
    }

}
