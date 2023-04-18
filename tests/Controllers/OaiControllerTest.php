<?php

namespace Terraformers\OpenArchive\Tests\Controllers;

use ReflectionClass;
use SilverStripe\Dev\FunctionalTest;
use SimpleXMLElement;
use Terraformers\OpenArchive\Controllers\OaiController;
use Terraformers\OpenArchive\Documents\OaiDocument;
use Terraformers\OpenArchive\Models\OaiRecord;

class OaiControllerTest extends FunctionalTest
{

    protected static $fixture_file = 'OaiControllerTest.yml';  // phpcs:ignore

    public function testXmlResponseHeader(): void
    {
        $response = $this->get('/api/v1/oai');

        $this->assertEquals('text/xml', $response->getHeader('Content-type'));
    }

    /**
     * @dataProvider badVerbUrlProvider
     */
    public function testBadVerbResponse(string $requestUrl): void
    {
        $response = $this->get($requestUrl);
        // Strip the get params for assertions to follow
        $requestUrl = substr($requestUrl, 0, strpos($requestUrl, '?'));

        $xml = simplexml_load_string($response->getBody());

        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $this->assertEquals('OAI-PMH', $xml->getName());
        $this->assertCount(3, $xml->children());
        $this->assertTrue(isset($xml->children()->error));
        $this->assertTrue(isset($xml->children()->responseDate));
        $this->assertTrue(isset($xml->children()->request));

        $error = $xml->children()->error;
        $request = $xml->children()->request;

        $this->assertTrue(isset($error->attributes()->code));
        $this->assertEquals(OaiDocument::ERROR_BAD_VERB, $error->attributes()->code);

        $this->assertStringContainsString($requestUrl, $request[0]);
    }

    /**
     * @dataProvider verbUrlProvider
     */
    public function testBasicVerbResponse(string $baseUrl, string $verb, ?string $metadataPrefix = null): void
    {
        $requestUrl = sprintf('%s?verb=%s', $baseUrl, $verb);

        if ($metadataPrefix) {
            $requestUrl = sprintf('%s&metadataPrefix=%s', $requestUrl, $metadataPrefix);
        }

        $response = $this->get($requestUrl);
        // Strip the get params for assertions to follow
        $requestUrl = substr($requestUrl, 0, strpos($requestUrl, '?'));

        $xml = simplexml_load_string($response->getBody());

        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $this->assertEquals('OAI-PMH', $xml->getName());
        $this->assertTrue(isset($xml->children()->responseDate));
        $this->assertTrue(isset($xml->children()->request));

        $request = $xml->children()->request;

        $this->assertTrue(isset($request->attributes()->verb));
        $this->assertEquals($verb, $request->attributes()->verb);
        $this->assertStringContainsString($requestUrl, $request[0]);
    }

    public function testIdentify(): void
    {
        $response = $this->get('/api/v1/oai?verb=Identify');

        $xml = simplexml_load_string($response->getBody());

        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $this->assertEquals('OAI-PMH', $xml->getName());
        $this->assertCount(3, $xml->children());
        $this->assertTrue(isset($xml->children()->responseDate));
        $this->assertTrue(isset($xml->children()->request));
        $this->assertTrue(isset($xml->children()->Identify));

        $identify = $xml->children()->Identify;

        $this->assertCount(8, $identify->children());
        $this->assertTrue(isset($identify->children()->baseURL));
        $this->assertTrue(isset($identify->children()->protocolVersion));
        $this->assertTrue(isset($identify->children()->deletedRecord));
        $this->assertTrue(isset($identify->children()->granularity));
        $this->assertTrue(isset($identify->children()->adminEmail));
        $this->assertTrue(isset($identify->children()->earliestDatestamp));
        $this->assertTrue(isset($identify->children()->repositoryName));
        $this->assertTrue(isset($identify->children()->description));
    }

    public function badVerbUrlProvider(): array
    {
        return [
            ['/api/v1/oai/badurl'],
            ['/api/v1/oai?verb=bad'],
            ['/api/v1/oai?other=bad'],
            ['/api/v1/oai'],
        ];
    }

    public function verbUrlProvider(): array
    {
        return [
            ['/api/v1/oai', OaiDocument::VERB_IDENTIFY],
            ['/api/v1/oai', OaiDocument::VERB_LIST_METADATA_FORMATS],
            ['/api/v1/oai', OaiDocument::VERB_LIST_RECORDS, 'oai_dc'],
            ['/api/v1/oai', OaiDocument::VERB_LIST_IDENTIFIERS, 'oai_dc'],
        ];
    }

    public function testFetchOaiRecords(): void
    {
        // Reflect the OaiController so we can access the protected function fetchOaiRecords()
        $controller = OaiController::create();
        $reflection = new ReflectionClass(OaiController::class);
        $method = $reflection->getMethod('fetchOaiRecords');
        $method->setAccessible(true);

        /** @var DOMElement $result */
        $result = $method->invoke($controller);
        $this->assertNotNull($result);

        // Create variables to access each YML file record
        $recordA = $this->objFromFixture(OaiRecord::class, 'recordA');
        $recordB = $this->objFromFixture(OaiRecord::class, 'recordB');
        $recordC = $this->objFromFixture(OaiRecord::class, 'recordC');

        // Check that the first and last items in the list match
        // their order in the YML file
        $this->assertEquals('Random Record A', $result->first()->Titles);
        $this->assertEquals('Random Record C', $result->last()->Titles);

        // Update the records with new titles and check that the LastEdited date has changed for each
        $recordAInitialEditDate = $recordA->LastEdited;
        $recordBInitialEditDate = $recordB->LastEdited;
        $recordCInitialEditDate = $recordC->LastEdited;

        sleep(1); // Pause is required so the items do not all update in the same millisecond
        $recordB->Titles = 'First Edited';
        $recordB->write();
        $this->assertNotEquals($recordBInitialEditDate, $recordB->LastEdited);

        sleep(1);
        $recordC->Titles = 'Second Edited';
        $recordC->write();
        $this->assertNotEquals($recordCInitialEditDate, $recordC->LastEdited);

        sleep(1);
        $recordA->Titles = 'Third Edited';
        $recordA->write();
        $this->assertNotEquals($recordAInitialEditDate, $recordA->LastEdited);

        // Update the result
        $result = $method->invoke($controller);

        // Check that the first and last items in the list now match the order in which they were edited
        $this->assertEquals('First Edited', $result->first()->Titles);
        $this->assertEquals('Third Edited', $result->last()->Titles);
    }
}
