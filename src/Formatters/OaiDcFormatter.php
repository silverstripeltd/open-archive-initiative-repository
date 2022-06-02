<?php

namespace Terraformers\OpenArchive\Formatters;

use DOMDocument;
use DOMElement;
use Terraformers\OpenArchive\Helpers\DateTimeHelper;
use Terraformers\OpenArchive\Helpers\RecordIdentityHelper;
use Terraformers\OpenArchive\Models\OaiRecord;

class OaiDcFormatter extends OaiRecordFormatter
{

    private const FIELD_HEADER_DATESTAMP = 'datestamp';
    private const FIELD_HEADER_IDENTIFIER = 'identifier';
    private const FIELD_HEADER_SET_SPEC = 'setSpec';
    private const FIELD_HEADER_STATUS = 'status';

    private const FIELD_CONTRIBUTOR = 'dc:contributor';
    private const FIELD_COVERAGE = 'dc:coverage';
    private const FIELD_CREATOR = 'dc:creator';
    private const FIELD_DATE = 'dc:date';
    private const FIELD_DESCRIPTION = 'dc:description';
    private const FIELD_FORMAT = 'dc:format';
    private const FIELD_IDENTIFIER = 'dc:identifier';
    private const FIELD_LANGUAGE = 'dc:language';
    private const FIELD_PUBLISHER = 'dc:publisher';
    private const FIELD_RELATION = 'dc:relation';
    private const FIELD_RIGHT = 'dc:rights';
    private const FIELD_SOURCE = 'dc:source';
    private const FIELD_SUBJECT = 'dc:subject';
    private const FIELD_TITLE = 'dc:title';
    private const FIELD_TYPE = 'dc:type';

    private const MANAGED_FIELDS = [
        self::FIELD_CONTRIBUTOR => OaiRecord::FIELD_CONTRIBUTORS,
        self::FIELD_COVERAGE => OaiRecord::FIELD_COVERAGES,
        self::FIELD_CREATOR => OaiRecord::FIELD_CREATORS,
        self::FIELD_DESCRIPTION => OaiRecord::FIELD_DESCRIPTIONS,
        self::FIELD_FORMAT => OaiRecord::FIELD_FORMATS,
        self::FIELD_IDENTIFIER => OaiRecord::FIELD_IDENTIFIER,
        self::FIELD_LANGUAGE => OaiRecord::FIELD_LANGUAGES,
        self::FIELD_PUBLISHER => OaiRecord::FIELD_PUBLISHERS,
        self::FIELD_RELATION => OaiRecord::FIELD_RELATIONS,
        self::FIELD_RIGHT => OaiRecord::FIELD_RIGHTS,
        self::FIELD_SOURCE => OaiRecord::FIELD_SOURCES,
        self::FIELD_SUBJECT => OaiRecord::FIELD_SUBJECTS,
        self::FIELD_TITLE => OaiRecord::FIELD_TITLES,
        self::FIELD_TYPE => OaiRecord::FIELD_TYPES,
    ];

    public function getMetadataPrefix(): string
    {
        return 'oai_dc';
    }

    public function getMetadataNamespaceUrl(): string
    {
        return 'http://www.openarchives.org/OAI/2.0/oai_dc/';
    }

    public function getSchemaUrl(): string
    {
        return 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';
    }

    public function generateDomElement(
        DOMDocument $document,
        OaiRecord $oaiRecord,
        bool $includeMetadata = false
    ): DOMElement {
        $identifier = RecordIdentityHelper::generateOaiIdentifier($oaiRecord);

        $headerElement = $document->createElement('header');

        $identifierField = $document->createElement(self::FIELD_HEADER_IDENTIFIER);
        $identifierField->nodeValue = $identifier;

        $headerElement->appendChild($identifierField);

        // The header datestamp represents the date in which the update became available to the API. This can differ
        // from the dc:date field in the metadata, which represents the date in which the associated resource was last
        // updated
        // Should these both be the same? No, not necessarily. datestamp is there for us to tell Harvesters that "we
        // need you to pull this record if you haven't done so since [this date]" - it doesn't matter *why* we are
        // requesting them to do so. The date field then indicates the date when the associated resource was last
        // updated, and the Harvesters can do whatever they like with that
        $datestampElement = $document->createElement(self::FIELD_HEADER_DATESTAMP);
        // All date strings must be presented as UTC+0
        $datestampElement->nodeValue = DateTimeHelper::getUtcStringFromLocal($oaiRecord->LastEdited);

        $headerElement->appendChild($datestampElement);

        foreach ($oaiRecord->OaiSets() as $oaiSet) {
            $setElement = $document->createElement(self::FIELD_HEADER_SET_SPEC);
            $setElement->nodeValue = $oaiSet->ID;

            $headerElement->appendChild($setElement);
        }

        if ($oaiRecord->Deleted) {
            $statusElement = $document->createElement(self::FIELD_HEADER_STATUS);
            $statusElement->nodeValue = 'deleted';

            $headerElement->appendChild($statusElement);
        }

        if (!$includeMetadata) {
            // For responses that do not include metadata, the header Element is returned as the root Element
            return $headerElement;
        }

        // When we are including metadata with our response, we need to wrap both the header and metadata elements
        // together into a root "record" element
        $recordElement = $document->createElement('record');
        // Append our header to the record Element (the header element is no longer our "root" element)
        $recordElement->appendChild($headerElement);

        // Generate our metadata Element
        $metadataElement = $document->createElement('metadata');
        // Similarly append the metadata Element to our root "record" Element
        $recordElement->appendChild($metadataElement);

        $oaiElement = $document->createElement('oai_dc:dc');
        $oaiElement->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $oaiElement->setAttribute('xmlns:oai_dc', $this->getMetadataNamespaceUrl());
        $oaiElement->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $oaiElement->setAttribute(
            'xsi:schemaLocation',
            sprintf('%s %s', $this->getMetadataNamespaceUrl(), $this->getSchemaUrl())
        );

        $metadataElement->appendChild($oaiElement);

        // Date field needs to be set a bit more manually, as we need to reformat the date
        $dateElement = $document->createElement(self::FIELD_DATE);
        // All date strings must be presented as UTC+0
        $dateElement->nodeValue = DateTimeHelper::getUtcStringFromLocal($oaiRecord->Date);

        $oaiElement->appendChild($dateElement);

        foreach (self::MANAGED_FIELDS as $elementName => $oaiRecordProperty) {
            $this->addMetadataElement($document, $oaiElement, $oaiRecord, $elementName, $oaiRecordProperty);
        }

        return $recordElement;
    }

    protected function addMetadataElement(
        DOMDocument $document,
        DOMElement $appendTo,
        OaiRecord $oaiRecord,
        string $elementName,
        string $property
    ): void {
        // If there is no value, then there is nothing for us to add here
        if (!$oaiRecord->{$property}) {
            return;
        }

        // If this field has been marked as *not* supporting CSV, then we just add one node with the value as is
        if (!OaiRecord::fieldSupportsCsv($property)) {
            $element = $document->createElement($elementName);
            $element->nodeValue = $oaiRecord->{$property};

            $appendTo->appendChild($element);

            return;
        }

        // If CSV values are marked as supported. Parse the field value as a CSV and create one element per value
        foreach (str_getcsv($oaiRecord->{$property}) as $content) {
            $element = $document->createElement($elementName);
            $element->nodeValue = $content;

            $appendTo->appendChild($element);
        }
    }

}
