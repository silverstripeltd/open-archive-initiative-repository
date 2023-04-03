<?php

namespace Terraformers\OpenArchive\Tests\Documents;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Documents\GetRecordDocument;
use Terraformers\OpenArchive\Extensions\VersionedOaiRecordManager;
use Terraformers\OpenArchive\Formatters\OaiDcFormatter;
use Terraformers\OpenArchive\Jobs\OaiRecordUpdateJob;
use Terraformers\OpenArchive\Tests\Mocks\SiteTreeExtension;

class GetRecordDocumentTest extends SapphireTest
{

    protected static $fixture_file = 'GetRecordDocumentTest.yml'; // phpcs:ignore

    /**
     * @var array
     */
    protected static $required_extensions = [
        SiteTree::class => [
            VersionedOaiRecordManager::class,
            SiteTreeExtension::class,
        ],
    ];

    public function testProcessOaiRecord(): void
    {
        // Mock OaiDcFormatter and mock GetRecordDocument
        $formatter = new OaiDcFormatter();
        $xmlDocument = GetRecordDocument::create($formatter);

        // Get a mock OaiRecord and Page record
        $page = $this->objFromFixture(SiteTree::class, 'page');
        $oaiRecord = $page->OaiRecords()->first();

        // Get the contents of the document
        $originalDocumentBody = $xmlDocument->getDocumentBody();

        // Confirm that this record has the contents of the mock file
        $this->assertNotNull($originalDocumentBody);
        $this->assertStringNotContainsString('<header>', $originalDocumentBody);
        $this->assertStringContainsString('OAI-PMH', $originalDocumentBody);
        $this->assertStringContainsString('<responseDate id="responseDate">', $originalDocumentBody);
        $this->assertStringContainsString(
            '<request id="request" verb="GetRecord" metadataPrefix="oai_dc"/>',
            $originalDocumentBody
        );
        $this->assertEquals('0', $oaiRecord->Deleted);

        // Run the test function with our mock Oai record
        $xmlDocument->processOaiRecord($oaiRecord);

        // Extract the document body after the main test has been executed
        $extractedDocumentBody = $xmlDocument->getDocumentBody();

        // Assert that we have the correct header attributes and that we do not see a 'status' element
        $this->assertStringContainsString('<header>', $extractedDocumentBody);
        $this->assertStringContainsString('<identifier>', $extractedDocumentBody);
        $this->assertStringContainsString('<datestamp>', $extractedDocumentBody);
        $this->assertStringNotContainsString('<header status="deleted">', $extractedDocumentBody);

        // Simulate the deletion of a document
        $page->doArchive();
        $job = OaiRecordUpdateJob::create();
        $job->hydrate($page->ClassName, $page->ID);
        $job->process();

        // Get the updated OaiRecord once the page has been archived
        $oaiRecordUpdated = $page->OaiRecords()->first();

        // Check that the updated OaiRecord has been deleted
        $this->assertEquals('1', $oaiRecordUpdated->Deleted);

        // Process the updated OaiRecord and get the processed DocumentBody
        $xmlDocument->processOaiRecord($oaiRecordUpdated);
        $processedDocumentBody = $xmlDocument->getDocumentBody();

        // Check that our 'header' element now contains a 'status' attribute
        $this->assertStringContainsString('<header status="deleted">', $processedDocumentBody);
        // There should also be a non-deleted header element present.
        $this->assertStringContainsString('<header>', $processedDocumentBody);
        $this->assertStringContainsString('<identifier>', $processedDocumentBody);
        $this->assertStringContainsString('<datestamp>', $processedDocumentBody);
    }

}
