<?php

namespace Terraformers\OpenArchive\Tests\Jobs;

use Terraformers\OpenArchive\Tests\Mocks\SiteTreeExtension;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Extensions\VersionedOaiRecordManager;
use Terraformers\OpenArchive\Jobs\OaiRecordUpdateJob;
use Terraformers\OpenArchive\Models\OaiRecord;

class OaiRecordUpdateJobTest extends SapphireTest
{

    protected static $fixture_file = 'OaiRecordUpdateJobTest.yml'; // phpcs:ignore

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     * @var array
     */
    protected static $required_extensions = [
        SiteTree::class => [
            VersionedOaiRecordManager::class,
            SiteTreeExtension::class,
        ],
    ];

    /**
     * @dataProvider updateOaiRecordProvider
     */
    public function testUpdateOaiRecord(string $fixtureId, int $initialCount): void
    {
        /** @var SiteTree|VersionedOaiRecordManager $page */
        $page = $this->objFromFixture(SiteTree::class, $fixtureId);
        $finalTitle = sprintf('%sEdit', $page->Title);

        // Check that we're set up correctly before we kick off
        $this->assertTrue($page->isPublished());
        $this->assertCount($initialCount, $page->OaiRecords());

        // If we expected there to be an initial record, then we'll check it is set up correctly as well
        if ($initialCount > 0) {
            /** @var OaiRecord $oaiRecord */
            $oaiRecord = $page->OaiRecords()->first();

            $this->assertEquals($page->Title, $oaiRecord->Title);
            $this->assertEquals(sprintf('subject1,subject2,%s', $page->Title), $oaiRecord->Subjects);
        }

        // Start modifying our page
        $page->Title = $finalTitle;
        $page->write();
        $page->publishRecursive();

        // Kick off our Job, which should either update or create the OaiRecord associated with our page
        $job = OaiRecordUpdateJob::create();
        $job->hydrate($page->ClassName, $page->ID);
        $job->process();

        // Check that we always have only 1 OaiRecord at the end of our process
        $this->assertCount(1, $page->OaiRecords());

        /** @var OaiRecord $oaiRecord */
        $oaiRecord = $page->OaiRecords()->first();

        // Check that each value set on our OaiRecord is as expected
        $this->assertEquals(0, $oaiRecord->Deleted);
        $this->assertEquals('CoverageValue', $oaiRecord->Coverage);
        $this->assertEquals($page->LastEdited, $oaiRecord->Date);
        $this->assertEquals('DescriptionValue', $oaiRecord->Description);
        $this->assertEquals('FormatValue', $oaiRecord->Format);
        $this->assertEquals($page->ID, $oaiRecord->Identifier);
        $this->assertEquals('LanguageValue', $oaiRecord->Language);
        $this->assertEquals('PublisherValue', $oaiRecord->Publisher);
        $this->assertEquals('RelationValue', $oaiRecord->Relation);
        $this->assertEquals('RightsValue', $oaiRecord->Rights);
        $this->assertEquals('SourceValue', $oaiRecord->Source);
        $this->assertEquals(sprintf('subject1,subject2,%s', $finalTitle), $oaiRecord->Subjects);
        $this->assertEquals($finalTitle, $oaiRecord->Title);
        $this->assertEquals('TypeValue', $oaiRecord->Type);
    }

    public function testMarkOaiRecordUnpublished(): void
    {
        /** @var SiteTree|VersionedOaiRecordManager $page */
        $page = $this->objFromFixture(SiteTree::class, 'page4');

        // Check that we're set up correctly
        $this->assertFalse($page->isPublished());
        $this->assertCount(1, $page->OaiRecords());

        /** @var OaiRecord $oaiRecord */
        $oaiRecord = $page->OaiRecords()->first();

        $this->assertEquals(0, $oaiRecord->Deleted);

        // Kick off our Job, which should mark the existing OaiRecord as deleted
        $job = OaiRecordUpdateJob::create();
        $job->hydrate($page->ClassName, $page->ID);
        $job->process();

        // Check that we always have only 1 OaiRecord at the end of our process
        $this->assertCount(1, $page->OaiRecords());

        /** @var OaiRecord $oaiRecord */
        $oaiRecord = $page->OaiRecords()->first();

        $this->assertEquals(1, $oaiRecord->Deleted);
    }

    public function testMarkOaiRecordArchived(): void
    {
        /** @var SiteTree|VersionedOaiRecordManager $page */
        $page = $this->objFromFixture(SiteTree::class, 'page5');

        // Check that we're set up correctly
        $this->assertFalse($page->isPublished());
        $this->assertCount(1, $page->OaiRecords());

        /** @var OaiRecord $oaiRecord */
        $oaiRecord = $page->OaiRecords()->first();

        $this->assertEquals(0, $oaiRecord->Deleted);

        // Archive our Page
        $page->doArchive();

        // Kick off our Job, which should mark the existing OaiRecord as deleted
        $job = OaiRecordUpdateJob::create();
        $job->hydrate($page->ClassName, $page->ID);
        $job->process();

        // Check that we always have only 1 OaiRecord at the end of our process
        $this->assertCount(1, $page->OaiRecords());

        /** @var OaiRecord $oaiRecord */
        $oaiRecord = $page->OaiRecords()->first();

        $this->assertEquals(1, $oaiRecord->Deleted);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $fixtureIds = [
            'page1',
            'page2',
            'page3',
        ];

        foreach ($fixtureIds as $fixtureId) {
            $page = $this->objFromFixture(SiteTree::class, $fixtureId);
            $page->publishRecursive();
        }
    }

    public function updateOaiRecordProvider(): array
    {
        return [
            ['page1', 1],
            ['page2', 2],
            ['page3', 0],
        ];
    }

}
