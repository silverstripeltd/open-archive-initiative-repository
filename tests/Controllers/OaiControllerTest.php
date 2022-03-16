<?php

namespace Terraformers\OpenArchive\Tests\Controllers;

use SilverStripe\Dev\FunctionalTest;
use SimpleXMLElement;
use Terraformers\OpenArchive\Documents\OaiDocument;

class OaiControllerTest extends FunctionalTest
{

    protected $usesDatabase = true; // phpcs:ignore

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
    public function testBasicVerbResponse(string $baseUrl, string $verb): void
    {
        $requestUrl = sprintf('%s?verb=%s', $baseUrl, $verb);

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

        $this->assertCount(7, $identify->children());
        $this->assertTrue(isset($identify->children()->baseURL));
        $this->assertTrue(isset($identify->children()->protocolVersion));
        $this->assertTrue(isset($identify->children()->deletedRecord));
        $this->assertTrue(isset($identify->children()->granularity));
        $this->assertTrue(isset($identify->children()->adminEmail));
        $this->assertTrue(isset($identify->children()->earliestDatestamp));
        $this->assertTrue(isset($identify->children()->repositoryName));
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
            ['/api/v1/oai', 'Identify'],
            ['/api/v1/oai', 'ListMetadataFormats'],
            ['/api/v1/oai', 'ListRecords'],
        ];
    }

}
