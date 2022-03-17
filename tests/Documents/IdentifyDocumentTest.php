<?php

namespace Terraformers\OpenArchive\Tests\Documents;

use DOMDocument;
use ReflectionClass;
use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Documents\IdentifyDocument;
use Terraformers\OpenArchive\Documents\OaiDocument;
use Terraformers\OpenArchive\Tests\Mocks\BaseDocument;

class IdentifyDocumentTest extends SapphireTest
{

    protected $usesDatabase = true; // phpcs:ignore

    public function testConstruct(): void
    {
        $document = IdentifyDocument::create();

        // Grab the DOMDocument so that we can access the DOMElement
        $domDocument = $this->getDomDocument($document);
        // Grab the DOMElement
        $requestElement = $domDocument->getElementById('request');

        // Check that the element and attribute exist, and that the attribute matches our first expected value
        $this->assertNotNull($requestElement);
        $this->assertTrue($requestElement->hasAttribute('verb'), 'Attribute not found');
        $this->assertEquals(OaiDocument::VERB_IDENTIFY, $requestElement->getAttribute('verb'));
    }

    /**
     * @param string $method
     * @param string $element
     * @param mixed $valueOne
     * @param mixed $valueTwo
     * @param mixed $expectedOne
     * @param mixed $expectedTwo
     * @return void
     * @dataProvider elementProvider
     */
    public function testElementSetters(
        string $method,
        string $element,
        $valueOne,
        $valueTwo,
        $expectedOne,
        $expectedTwo
    ): void {
        $document = IdentifyDocument::create();
        // Set the initial value
        $document->{$method}($valueOne);

        // Grab the DOMDocument so that we can access the DOMElement
        $domDocument = $this->getDomDocument($document);
        // Grab the DOMElement
        $domElement = $domDocument->getElementById($element);

        $this->assertNotNull($domElement, sprintf('%s not found', $element));
        $this->assertEquals($expectedOne, $domElement->nodeValue);

        // Set the element to a new value. Note, this should update the DOMElement that we already have instantiated
        // above
        $document->{$method}($valueTwo);

        // Check that the date was updated
        $this->assertEquals($expectedTwo, $domElement->nodeValue);
    }

    public function testSetOaiIdentifier(): void
    {
        $document = IdentifyDocument::create();
        $document->setOaiIdentifier('localhost.test', 1);

        // Grab the DOMDocument so that we can access the DOMElement
        $domDocument = $this->getDomDocument($document);
        // Grab the DOMElement
        $descriptionElement = $domDocument->getElementById('description');

        $this->assertNotNull($descriptionElement);
        $this->assertCount(1, $descriptionElement->childNodes);

        $oaiIdentifierElement = $descriptionElement->firstChild;

        $this->assertNotNull($oaiIdentifierElement);
        $this->assertCount(4, $oaiIdentifierElement->childNodes);

        $scheme = $oaiIdentifierElement->getElementsByTagName('scheme')->item(0);
        $delimiter = $oaiIdentifierElement->getElementsByTagName('delimiter')->item(0);
        $repoIdentifier = $oaiIdentifierElement->getElementsByTagName('repositoryIdentifier')->item(0);
        $sampleIdentifier = $oaiIdentifierElement->getElementsByTagName('sampleIdentifier')->item(0);

        $this->assertNotNull($scheme);
        $this->assertEquals('oai', $scheme->nodeValue);
        $this->assertNotNull($delimiter);
        $this->assertEquals(':', $delimiter->nodeValue);
        $this->assertNotNull($repoIdentifier);
        $this->assertEquals('localhost.test', $repoIdentifier->nodeValue);
        $this->assertNotNull($sampleIdentifier);
        $this->assertEquals('oai:localhost.test:1', $sampleIdentifier->nodeValue);
    }

    protected function getDomDocument(OaiDocument $document): DOMDocument
    {
        $reflection = new ReflectionClass(BaseDocument::class);
        $property = $reflection->getProperty('document');
        $property->setAccessible(true);

        return $property->getValue($document);
    }

    public function elementProvider(): array
    {
        return [
            [
                'setRepositoryName',
                'repositoryName',
                'repo1',
                'repo2',
                'repo1',
                'repo2',
            ],
            [
                'setBaseURL',
                'baseURL',
                'url1',
                'url2',
                'url1',
                'url2',
            ],
            [
                'setProtocolVersion',
                'protocolVersion',
                1.0,
                2.0,
                1.0,
                2.0,
            ],
            [
                'setAdminEmail',
                'adminEmail',
                'email1@test.com',
                'email2@test.com',
                'email1@test.com',
                'email2@test.com',
            ],
            [
                'setEarliestDatestamp',
                'earliestDatestamp',
                0,
                60,
                '1970-01-01T12:00:00Z',
                '1970-01-01T12:01:00Z',
            ],
            [
                'setDeletedRecord',
                'deletedRecord',
                'no',
                'persistent',
                'no',
                'persistent',
            ],
            [
                'setGranularity',
                'granularity',
                'YYYY-MM-DDThh:mm:ssZ',
                'YYYY-MM-DDT',
                'YYYY-MM-DDThh:mm:ssZ',
                'YYYY-MM-DDT',
            ],
        ];
    }

}
