<?php

namespace Terraformers\OpenArchive\Tests\Mocks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataExtension;

/**
 * @property SiteTree|$this $owner
 */
class SiteTreeExtension extends DataExtension
{

    /**
     * Simple test of OaiRecord fields, some pointing to model properties, and the others pointing to model methods
     */
    private static array $oai_fields = [
        'Coverage' => 'getCoverage',
        'Description' => 'MetaDescription',
        'Format' => 'getFormat',
        'Language' => 'getLanguage',
        'Publisher' => 'getPublisher',
        'Relation' => 'getRelation',
        'Rights' => 'getRights',
        'Source' => 'getSource',
        'Subjects' => 'getSubjects',
        'Title' => 'Title',
        'Type' => 'getType',
    ];

    public function getCoverage(): string
    {
        return 'CoverageValue';
    }

    public function getFormat(): string
    {
        return 'FormatValue';
    }

    public function getLanguage(): string
    {
        return 'LanguageValue';
    }

    public function getPublisher(): string
    {
        return 'PublisherValue';
    }

    public function getRelation(): string
    {
        return 'RelationValue';
    }

    public function getRights(): string
    {
        return 'RightsValue';
    }

    public function getSource(): string
    {
        return 'SourceValue';
    }

    public function getSubjects(): string
    {
        // Two hardcoded values plus one dynamic so that we can check it changes
        return implode(
            ',',
            [
                'subject1',
                'subject2',
                $this->owner->Title,
            ]
        );
    }

    public function getType(): string
    {
        return 'TypeValue';
    }

}
