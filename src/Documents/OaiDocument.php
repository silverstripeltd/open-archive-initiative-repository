<?php

namespace Terraformers\OpenArchive\Documents;

use DOMDocument;
use DOMElement;
use Exception;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\FieldType\DBDatetime;
use Terraformers\OpenArchive\Helpers\DateTimeHelper;

/**
 * This abstract class provides you with a basic XML structure that every OAI response needs to follow. That being:
 *
 * - Appropriate XML UTF-8 definition
 * - A document root element called OAI-PMH
 * - Appropriate schema defintions for the document root
 * - The date when the response was generated
 *
 * Additionally, there are then method for you to manage other standard OAI response fields. That being:
 *
 * - Request URL and Verb
 * - Error code
 */
abstract class OaiDocument
{

    use Injectable;

    public const ERROR_BAD_ARGUMENT = 'badArgument';
    public const ERROR_BAD_RESUMPTION_TOKEN = 'badResumptionToken';
    public const ERROR_BAD_VERB = 'badVerb';
    public const ERROR_CANNOT_DISSEMINATE_FORMAT = 'cannotDisseminateFormat';
    public const ERROR_ID_DOES_NOT_EXIST = 'idDoesNotExist';
    public const ERROR_NO_METADATA_FORMATS = 'noMetadataFormats';
    public const ERROR_NO_RECORDS_MATCH = 'noRecordsMatch';
    public const ERROR_NO_SET_HIERARCHY = 'noSetHierarchy';

    public const VERB_GET_RECORD = 'GetRecord';
    public const VERB_IDENTIFY = 'Identify';
    public const VERB_LIST_IDENTIFIERS = 'ListIdentifiers';
    public const VERB_LIST_METADATA_FORMATS = 'ListMetadataFormats';
    public const VERB_LIST_RECORDS = 'ListRecords';
    public const VERB_LIST_SETS = 'ListSets';

    public const ERROR_CODES = [
        self::ERROR_BAD_ARGUMENT,
        self::ERROR_BAD_RESUMPTION_TOKEN,
        self::ERROR_BAD_VERB,
        self::ERROR_CANNOT_DISSEMINATE_FORMAT,
        self::ERROR_ID_DOES_NOT_EXIST,
        self::ERROR_NO_METADATA_FORMATS,
        self::ERROR_NO_RECORDS_MATCH,
        self::ERROR_NO_SET_HIERARCHY,
    ];

    protected DOMDocument $document;

    public function __construct()
    {
        $this->document = new DOMDocument('1.0', 'UTF-8');

        // To have indented output rather than a single line
        $this->document->preserveWhiteSpace = false;
        $this->document->formatOutput = true;

        // Create the root element of the xml tree
        $rootElement = $this->document->createElement('OAI-PMH');
        $rootElement->setAttribute('id', 'OAI-PMH');
        $rootElement->setIdAttribute('id', true);
        $rootElement->setAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/');
        $rootElement->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $rootElement->setAttribute(
            'xsi:schemaLocation',
            'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd'
        );

        // Append it to our primary document
        $this->document->appendChild($rootElement);
        // Set the Response date. Default is simply the time of instantiation
        $this->setResponseDate();
    }

    public function getDocumentBody(): string
    {
        return $this->document->saveXML();
    }

    public function setResponseDate(?string $dateString = null): void
    {
        if (!$dateString) {
            $dateString = DateTimeHelper::getUtcStringFromLocal(date('Y-m-d H:i:s', DBDatetime::now()->getTimestamp()));
        }

        // Check to see if we have an existing responseDate element (we can only have 1)
        $responseDateElement = $this->document->getElementById('responseDate');

        // If we don't already have a responseDate element, then we'll create one
        if (!$responseDateElement) {
            $responseDateElement = $this->document->createElement('responseDate');
            $responseDateElement->setAttribute('id', 'responseDate');
            $responseDateElement->setIdAttribute('id', true);

            $this->getRootElement()->appendChild($responseDateElement);
        }

        // Set the value of our responseDate field
        $responseDateElement->nodeValue = $dateString;
    }

    public function setRequestUrl(string $requestUrl): void
    {
        $requestElement = $this->findOrCreateElement('request');
        $requestElement->nodeValue = $requestUrl;
    }

    public function setRequestSpec(string $spec): void
    {
        $requestElement = $this->findOrCreateElement('request');
        $requestElement->setAttribute('metadataPrefix', $spec);
    }

    public function setRequestVerb(string $verb): void
    {
        $requestElement = $this->findOrCreateElement('request');
        $requestElement->setAttribute('verb', $verb);
    }

    public function setMetadataPrefix(string $metadataPrefix): void
    {
        $requestElement = $this->findOrCreateElement('request');
        $requestElement->setAttribute('metadataPrefix', $metadataPrefix);
    }

    public function addError(string $errorCode, ?string $errorMessage = null): void
    {
        // Check that the error code is one that is supported by the OAI spec
        // @see http://www.openarchives.org/OAI/openarchivesprotocol.html#ErrorConditions
        if (!in_array($errorCode, self::ERROR_CODES, true)) {
            throw new Exception('Unknown error code provided');
        }

        // We're allowed to add as many valid error codes as we like
        $errorElement = $this->document->createElement('error');
        $errorElement->setAttribute('code', $errorCode);

        if ($errorMessage) {
            $errorElement->nodeValue = $errorMessage;
        }

        $this->getRootElement()->appendChild($errorElement);
    }

    public function hasErrors(): bool
    {
        return (bool) $this->getRootElement()->getElementsByTagName('error')->count();
    }

    protected function getRootElement(): DOMElement
    {
        // Fetch our root element
        $rootElement = $this->document->getElementById('OAI-PMH');

        // We expect the root element to have been created during instantiation
        if (!$rootElement) {
            throw new Exception('Unable to find XML root');
        }

        return $rootElement;
    }

    protected function findOrCreateElement(string $id, ?DOMElement $appendTo = null): DOMElement
    {
        // If no $appendTo DOMElement was provided, then we're going to use the root element
        if (!$appendTo) {
            $appendTo = $this->getRootElement();
        }

        // Check to see if we have an existing element (we can only have 1)
        $domElement = $this->document->getElementById($id);

        // The element doesn't exist, we'll create one and add it to the requested parent DOMElement
        if (!$domElement) {
            $domElement = $this->document->createElement($id);
            $domElement->setAttribute('id', $id);
            $domElement->setIdAttribute('id', true);

            $appendTo->appendChild($domElement);
        }

        return $domElement;
    }

}
