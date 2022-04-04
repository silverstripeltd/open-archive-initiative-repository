<?php

namespace Terraformers\OpenArchive\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyThroughList;
use Terraformers\OpenArchive\Models\Relationships\OaiRecordOaiSet;

/**
 * All MANAGED_FIELDS support CSV format:
 * Separator = ",", Enclosure = '"', Escape = "\\"
 *
 * Be sure to enclose any values that contain commas
 *
 * Why CSV? Because OAI spec does not restrict the number of fields that can be provided for any given record
 *
 * @property string $Contributors
 * @property string $Coverages
 * @property string $Creators
 * @property string $Date Note: This is the date when the related DataObject was updated. Not to be confused with the
 * header datestamp which will be the $LastEdited value from the OAI Record (see below)
 * @property string $Descriptions
 * @property int $Deleted
 * @property string $Formats
 * @property string $Identifier Note: This should be a URL to the DataObject (Page, File, whatever). Not to be confused
 * with the header Identifier (which will be automatically generated)
 * @property string $LastEdited Note: This is the date that we use for filtering. This represents the date in which
 * the update *became available in the API*
 * @property string $Languages
 * @property string $Publishers
 * @property string $RecordClass
 * @property int $RecordID
 * @property string $Relations
 * @property string $Rights
 * @property string $Sources
 * @property string $Subjects
 * @property string $Titles
 * @property string $Types
 * @method HasManyList|OaiRecordOaiSet[] OaiRecordOaiSets()
 * @method ManyManyThroughList|OaiSet[] OaiSets()
 */
class OaiRecord extends DataObject
{

    public const FIELD_CONTRIBUTORS = 'Contributors';
    public const FIELD_COVERAGES = 'Coverages';
    public const FIELD_CREATORS = 'Creators';
    public const FIELD_DESCRIPTIONS = 'Descriptions';
    public const FIELD_DATE = 'Date';
    public const FIELD_DELETED = 'Deleted';
    public const FIELD_FORMATS = 'Formats';
    public const FIELD_IDENTIFIER = 'Identifier';
    public const FIELD_LANGUAGES = 'Languages';
    public const FIELD_PUBLISHERS = 'Publishers';
    public const FIELD_RELATIONS = 'Relations';
    public const FIELD_RIGHTS = 'Rights';
    public const FIELD_SOURCES = 'Sources';
    public const FIELD_SUBJECTS = 'Subjects';
    public const FIELD_TITLES = 'Titles';
    public const FIELD_TYPES = 'Types';

    public const MANAGED_FIELDS = [
        self::FIELD_CONTRIBUTORS,
        self::FIELD_COVERAGES,
        self::FIELD_CREATORS,
        self::FIELD_DESCRIPTIONS,
        self::FIELD_FORMATS,
        self::FIELD_IDENTIFIER,
        self::FIELD_LANGUAGES,
        self::FIELD_PUBLISHERS,
        self::FIELD_RELATIONS,
        self::FIELD_RIGHTS,
        self::FIELD_SOURCES,
        self::FIELD_SUBJECTS,
        self::FIELD_TITLES,
        self::FIELD_TYPES,
    ];

    private static string $table_name = 'OaiRecord';

    private static array $db = [
        self::FIELD_CONTRIBUTORS => 'Varchar(255)',
        self::FIELD_COVERAGES => 'Varchar(255)',
        self::FIELD_CREATORS => 'Varchar(255)',
        self::FIELD_DATE => 'Datetime',
        self::FIELD_DESCRIPTIONS => 'Text',
        self::FIELD_DELETED => 'Boolean(0)',
        self::FIELD_FORMATS => 'Varchar(255)',
        self::FIELD_IDENTIFIER => 'Varchar(255)',
        self::FIELD_LANGUAGES => 'Varchar(255)',
        self::FIELD_PUBLISHERS => 'Varchar(255)',
        self::FIELD_RELATIONS => 'Varchar(255)',
        self::FIELD_RIGHTS => 'Varchar(255)',
        self::FIELD_SOURCES => 'Varchar(255)',
        self::FIELD_SUBJECTS => 'Text',
        self::FIELD_TITLES => 'Varchar(255)',
        self::FIELD_TYPES => 'Varchar(255)',
    ];

    private static array $has_one = [
        'Record' => DataObject::class,
    ];

    private static array $has_many = [
        'OaiRecordOaiSets' => OaiRecordOaiSet::class . '.Parent',
    ];

    private static array $many_many = [
        'OaiSets' => [
            'through' => OaiRecordOaiSet::class,
            'from' => 'Parent',
            'to' => 'Set',
        ],
    ];

    private static string $default_sort = 'ID ASC';

    public function addSet(string $title): void
    {
        $this->OaiSets()->add(OaiSet::findOrCreate($title));
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
