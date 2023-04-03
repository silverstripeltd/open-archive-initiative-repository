<?php

namespace Terraformers\OpenArchive\Tests\Formatters;

use DOMDocument;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Extensions\VersionedOaiRecordManager;
use Terraformers\OpenArchive\Formatters\OaiDcFormatter;
use Terraformers\OpenArchive\Tests\Mocks\SiteTreeExtension;

class OaiDcFormatterTest extends SapphireTest
{

    protected static $fixture_file = 'OaiDcFormatterTest.yml'; // phpcs:ignore

    /**
     * @var array
     */
    protected static $required_extensions = [
        SiteTree::class => [
            VersionedOaiRecordManager::class,
            SiteTreeExtension::class,
        ],
    ];

    public function testGenerateDomElement(): void
    {
        // Mock OaiDcFormatter and mock DOMDocument
        $formatter = new OaiDcFormatter();
        $document = new DOMDocument();

        // Get a mock OaiRecord and Page record
        $page = $this->objFromFixture(SiteTree::class, 'page');
        $oaiRecord = $page->OaiRecords()->first();

        // Test the function with our mock fixtures
        $result = $formatter->generateDomElement($document, $oaiRecord, true);

        // Check our DOMElement exists, that we have the 'oai' tag, and check the page title
        $this->assertNotNull($result);
        $this->assertStringContainsString('oai', $result->nodeValue);
        $this->assertStringContainsString('Record Titles', $result->nodeValue);
    }

}
