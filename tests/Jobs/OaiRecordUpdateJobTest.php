<?php

namespace Terraformers\OpenArchive\Tests\Jobs;

use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Extensions\VersionedOaiRecordManager;
use Terraformers\OpenArchive\Jobs\OaiRecordUpdateJob;
use Terraformers\OpenArchive\Models\OaiRecord;
use Terraformers\OpenArchive\Tests\Mocks\FileExtension;
use Terraformers\OpenArchive\Tests\Mocks\SiteTreeExtension;

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
        File::class => [
            VersionedOaiRecordManager::class,
            FileExtension::class,
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
            $this->assertEquals(sprintf('rights1,rights2,%s', $page->Title), $oaiRecord->Rights);
            $this->assertEquals(sprintf('subject1,subject2,%s', $page->Title), $oaiRecord->Subjects);
            $this->assertEquals(sprintf('type1,type2,%s', $page->Title), $oaiRecord->Types);
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
        $this->assertEquals('DescriptionValue', $oaiRecord->Description);
        $this->assertEquals('FormatValue', $oaiRecord->Format);
        $this->assertEquals('LanguageValue', $oaiRecord->Language);
        $this->assertEquals('PublisherValue', $oaiRecord->Publisher);
        $this->assertEquals('RelationValue', $oaiRecord->Relation);
        $this->assertEquals(sprintf('rights1,rights2,%s', $finalTitle), $oaiRecord->Rights);
        $this->assertEquals('SourceValue', $oaiRecord->Source);
        $this->assertEquals(sprintf('subject1,subject2,%s', $finalTitle), $oaiRecord->Subjects);
        $this->assertEquals($finalTitle, $oaiRecord->Title);
        $this->assertEquals(sprintf('type1,type2,%s', $finalTitle), $oaiRecord->Types);
    }

    public function testCanUpdateOaiRecord(): void
    {
        /** @var SiteTree|VersionedOaiRecordManager $file */
        $file = $this->objFromFixture(File::class, 'file1');
        $file->setFromLocalFile(dirname(__FILE__) . '/../Mocks/file.pdf');
        $file->write();

        // Make sure the File is published
        $file->publishRecursive();

        // Check that we're set up correctly before we kick off
        $this->assertCount(0, $file->OaiRecords());

        // Kick off our Job, which should create the OaiRecord associated with our file
        $job = OaiRecordUpdateJob::create();
        $job->hydrate($file->ClassName, $file->ID);
        $job->process();

        // Check that we have 1 OaiRecord at the end of our process
        $this->assertCount(1, $file->OaiRecords());

        /** @var OaiRecord $oaiRecord */
        $oaiRecord = $file->OaiRecords()->first();

        // And check that the OaiRecord is marked as active
        $this->assertEquals(0, $oaiRecord->Deleted);

        // The extension we have applied should mark this record as "not being able to be updated" when the Title is
        // set to DeleteMe
        $file->Title = 'DeleteMe';
        $file->write();
        $file->publishRecursive();

        // Kick off our Job, which update the OaiRecord to deleted
        $job = OaiRecordUpdateJob::create();
        $job->hydrate($file->ClassName, $file->ID);
        $job->process();

        // Check that we always have only 1 OaiRecord at the end of our process
        $this->assertCount(1, $file->OaiRecords());

        /** @var OaiRecord $oaiRecord */
        $oaiRecord = $file->OaiRecords()->first();

        // Check that the OaiRecord is now marked as deleted
        $this->assertEquals(1, $oaiRecord->Deleted);
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
