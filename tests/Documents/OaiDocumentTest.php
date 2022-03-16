<?php

namespace Terraformers\OpenArchive\Tests\Documents;

use DOMDocument;
use DOMElement;
use ReflectionClass;
use SilverStripe\Dev\SapphireTest;
use Terraformers\OpenArchive\Documents\OaiDocument;
use Terraformers\OpenArchive\Tests\Mocks\BaseDocument;

class OaiDocumentTest extends SapphireTest
{

    protected $usesDatabase = true; // phpcs:ignore

    public function testConstruct(): void
    {
        $document = BaseDocument::create();
        $reflection = new ReflectionClass(BaseDocument::class);
        $property = $reflection->getMethod('getRootElement');
        $property->setAccessible(true);

        /** @var DOMElement $rootElement */
        $rootElement = $property->invoke($document);

        $this->assertEquals('OAI-PMH', $rootElement->localName);

        $this->assertTrue($rootElement->hasAttribute('xmlns'), 'xmlns not found');
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
        $document = BaseDocument::create();
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

    /**
     * @dataProvider attributeProvider
     */
    public function testAttributeSetters(string $method, string $element, string $attribute): void
    {
        $valueOne = 'value1';
        $valueTwo = 'value2';

        $document = BaseDocument::create();
        // Set the attribute value to our first value
        $document->{$method}($valueOne);

        // Grab the DOMDocument so that we can access the DOMElement
        $domDocument = $this->getDomDocument($document);
        // Grab the DOMElement
        $domElement = $domDocument->getElementById($element);

        // Check that the element and attribute exist, and that the attribute matches our first expected value
        $this->assertNotNull($domElement);
        $this->assertTrue($domElement->hasAttribute($attribute), 'Attribute not found');
        $this->assertEquals($valueOne, $domElement->getAttribute($attribute));

        // Set the attribute to something else. Note, this should update the DOMElement that we already have
        // instantiated above
        $document->{$method}($valueTwo);

        // Check that the spec was updated
        $this->assertTrue($domElement->hasAttribute($attribute), 'Attribute not found');
        $this->assertEquals($valueTwo, $domElement->getAttribute($attribute));
    }

    public function testAddError(): void
    {
        $document = BaseDocument::create();
        // Add our errors
        $document->addError(OaiDocument::ERROR_BAD_VERB);
        $document->addError(OaiDocument::ERROR_BAD_RESUMPTION_TOKEN);

        // Grab the DOMDocument so that we can access the DOMElement
        $domDocument = $this->getDomDocument($document);

        $this->assertCount(2, $domDocument->getElementsByTagName('error'));
        $this->assertEquals(
            OaiDocument::ERROR_BAD_VERB,
            $domDocument->getElementsByTagName('error')->item(0)->attributes['code']->value
        );
        $this->assertEquals(
            OaiDocument::ERROR_BAD_RESUMPTION_TOKEN,
            $domDocument->getElementsByTagName('error')->item(1)->attributes['code']->value
        );
    }

    public function testAddErrorException(): void
    {
        $this->expectExceptionMessage('Unknown error code provided');

        $document = BaseDocument::create();
        // Add our errors
        $document->addError('badErrorCode');
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
                'setResponseDate',
                'responseDate',
                0,
                60,
                '1970-01-01T12:00:00Z',
                '1970-01-01T12:01:00Z',
            ],
            [
                'setRequestUrl',
                'request',
                'https://localhost.test/api/v1/oai',
                'https://localhost.local/api/v1/oai',
                'https://localhost.test/api/v1/oai',
                'https://localhost.local/api/v1/oai',
            ],
        ];
    }

    public function attributeProvider(): array
    {
        return [
            ['setRequestSpec', 'request', 'metadataPrefix'],
            ['setRequestVerb', 'request', 'verb'],
        ];
    }

}
