<?php

namespace Terraformers\OpenArchive\Formatters;

use DOMDocument;
use DOMElement;
use SilverStripe\Control\Director;
use Terraformers\OpenArchive\Models\OaiRecord;

class OaiDcFormatter extends OaiRecordFormatter
{

    public function getMetadataPrefix(): string
    {
        return 'oai_dc';
    }

    public function generateDomElement(
        DOMDocument $document,
        OaiRecord $oaiRecord,
        bool $includeMetadata = false
    ): DOMElement {
        $identifier = $this->getIdentifier($oaiRecord);
        $rootElement = $document->createElement('record');
        $headerElement = $document->createElement('header');

        $rootElement->appendChild($headerElement);

        $identifierField = $document->createElement('identifier');
        $identifierField->nodeValue = $identifier;

        $headerElement->appendChild($identifierField);

        $datestampElement = $document->createElement('datestamp');
        $datestampElement->nodeValue = date('Y-m-d\Th:i:s\Z', strtotime($oaiRecord->LastEdited));

        $headerElement->appendChild($datestampElement);

        foreach ($oaiRecord->OaiSets() as $oaiSet) {
            $setElement = $document->createElement('setSpec');
            $setElement->nodeValue = $oaiSet->ID;

            $headerElement->appendChild($setElement);
        }

        if ($oaiRecord->Deleted) {
            $statusElement = $document->createElement('status');
            $statusElement->nodeValue = 'deleted';

            $headerElement->appendChild($statusElement);
        }

        if (!$includeMetadata) {
            return $rootElement;
        }

        $metadataElement = $document->createElement('metadata');

        $rootElement->appendChild($metadataElement);

        $oaiElement = $document->createElement('oai_dc:dc');
        $oaiElement->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $oaiElement->setAttribute('xmlns:oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
        $oaiElement->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $oaiElement->setAttribute(
            'xsi:schemaLocation',
            'http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd'
        );

        $metadataElement->appendChild($oaiElement);

        // Special case for Subjects as they are stored in CSV format
        if ($oaiRecord->{OaiRecord::FIELD_SUBJECTS}) {
            // We want one Element per subject
            foreach (str_getcsv($oaiRecord->{OaiRecord::FIELD_SUBJECTS}) as $subject) {
                $element = $document->createElement('dc:subject');
                $element->nodeValue = $subject;

                $oaiElement->appendChild($element);
            }
        }

        // Special case for Subjects as they are stored in CSV format
        if ($oaiRecord->{OaiRecord::FIELD_TYPE}) {
            // We want one Element per subject
            foreach (str_getcsv($oaiRecord->{OaiRecord::FIELD_TYPE}) as $type) {
                $element = $document->createElement('dc:type');
                $element->nodeValue = $type;

                $oaiElement->appendChild($element);
            }
        }

        // Special case for Rights as they are stored in CSV format
        if ($oaiRecord->{OaiRecord::FIELD_RIGHTS}) {
            // We want one Element per subject
            foreach (str_getcsv($oaiRecord->{OaiRecord::FIELD_RIGHTS}) as $type) {
                $element = $document->createElement('dc:rights');
                $element->nodeValue = $type;

                $oaiElement->appendChild($element);
            }
        }

        $dateElement = $document->createElement('dc:date');
        $dateElement->nodeValue = date('Y-m-d\Th:i:s\Z', strtotime($oaiRecord->LastEdited));

        $oaiElement->appendChild($dateElement);

        $identifierField = $document->createElement('dc:identifier');
        $identifierField->nodeValue = $identifier;

        $oaiElement->appendChild($identifierField);

        $this->addMetadataElement($document, $oaiElement, $oaiRecord, 'dc:coverage', OaiRecord::FIELD_COVERAGE);
        $this->addMetadataElement($document, $oaiElement, $oaiRecord, 'dc:description', OaiRecord::FIELD_DESCRIPTION);
        $this->addMetadataElement($document, $oaiElement, $oaiRecord, 'dc:format', OaiRecord::FIELD_FORMAT);
        $this->addMetadataElement($document, $oaiElement, $oaiRecord, 'dc:language', OaiRecord::FIELD_LANGUAGE);
        $this->addMetadataElement($document, $oaiElement, $oaiRecord, 'dc:publisher', OaiRecord::FIELD_PUBLISHER);
        $this->addMetadataElement($document, $oaiElement, $oaiRecord, 'dc:relation', OaiRecord::FIELD_RELATION);
        $this->addMetadataElement($document, $oaiElement, $oaiRecord, 'dc:source', OaiRecord::FIELD_SOURCE);
        $this->addMetadataElement($document, $oaiElement, $oaiRecord, 'dc:title', OaiRecord::FIELD_TITLE);

        return $rootElement;
    }

    protected function getIdentifier(OaiRecord $oaiRecord): string
    {
        return sprintf('oai:%s:%s', Director::host(), $oaiRecord->ID);
    }

    protected function addMetadataElement(
        DOMDocument $document,
        DOMElement $appendTo,
        OaiRecord $oaiRecord,
        string $elementName,
        string $property
    ): void {
        if (!$oaiRecord->{$property}) {
            return;
        }

        $element = $document->createElement($elementName);
        $element->nodeValue = $oaiRecord->{$property};

        $appendTo->appendChild($element);
    }

}
