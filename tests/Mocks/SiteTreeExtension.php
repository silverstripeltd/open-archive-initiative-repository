<?php

namespace Terraformers\OpenArchive\Tests\Mocks;

use SilverStripe\ORM\DataExtension;

class SiteTreeExtension extends DataExtension
{

    /**
     * Simple test of two OaiRecord fields, one pointing to a model property, and the other pointing to a model method
     */
    private static array $oai_fields = [
        'Title' => 'Title',
        'Subjects' => 'getSubjects',
    ];

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

}
