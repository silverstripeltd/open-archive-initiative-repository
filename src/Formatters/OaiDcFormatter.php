<?php

namespace Terraformers\OpenArchive\Formatters;

use DOMElement;
use SilverStripe\Control\Director;
use Terraformers\OpenArchive\Models\OaiRecord;

class OaiDcFormatter extends OaiRecordFormatter
{

    public function getMetadataPrefix(): string
    {
        return 'oai_dc';
    }

    public function generateDomElement(OaiRecord $oaiRecord, bool $includeMetadata = false): DOMElement
    {
        $rootElement = new DOMElement('record');
        $headerElement = new DOMElement('header');

        $rootElement->appendChild($headerElement);

        $identifierField = new DOMElement('identifier');
        $identifierField->nodeValue = $this->getIdentifier($oaiRecord);

        $headerElement->appendChild($identifierField);

        $datestampElement = new DOMElement('datestamp');
        $datestampElement->nodeValue = date('Y-m-d\Th:i:s\Z', strtotime($oaiRecord->LastEdited));

        $headerElement->appendChild($datestampElement);

        foreach ($oaiRecord->OaiSets() as $oaiSet) {
            $setElement = new DOMElement('setSpec');
            $setElement->nodeValue = $oaiSet->ID;

            $headerElement->appendChild($setElement);
        }

        if ($oaiRecord->Deleted) {
            $statusElement = new DOMElement('status');
            $statusElement->nodeValue = 'deleted';

            $headerElement->appendChild($statusElement);
        }

        if (!$includeMetadata) {
            return $rootElement;
        }

        $metadataElement = new DOMElement('metadata');

        $rootElement->appendChild($metadataElement);

        $oaiElement = new DOMElement('oai_dc:dc');
        $oaiElement->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $oaiElement->setAttribute('xmlns:oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
        $oaiElement->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $oaiElement->setAttribute(
            'xsi:schemaLocation',
            'http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd'
        );

        $metadataElement->appendChild($oaiElement);

        // Special case for Subjects as they are stored in CSV format
        if ($oaiRecord->Subjects) {
            // We want one Element per subject
            foreach (str_getcsv($oaiRecord->Subjects) as $subject) {
                $element = new DOMElement('dc:subject');
                $element->nodeValue = $subject;

                $oaiElement->appendChild($element);
            }
        }

        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:coverage', OaiRecord::FIELD_COVERAGE);
        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:description', OaiRecord::FIELD_DESCRIPTION);
        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:format', OaiRecord::FIELD_FORMAT);
        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:language', OaiRecord::FIELD_LANGUAGE);
        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:publisher', OaiRecord::FIELD_PUBLISHER);
        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:relation', OaiRecord::FIELD_RELATION);
        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:rights', OaiRecord::FIELD_RIGHTS);
        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:source', OaiRecord::FIELD_SOURCE);
        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:title', OaiRecord::FIELD_TITLE);
        $this->addMetadataElement($metadataElement, $oaiRecord, 'dc:type', OaiRecord::FIELD_TYPE);

        return $rootElement;
    }

    protected function getIdentifier(OaiRecord $oaiRecord): string
    {
        return sprintf('oai:%s:%s', Director::host(), $oaiRecord->ID);
    }

    protected function addMetadataElement(
        DOMElement $metadataElement,
        OaiRecord $oaiRecord,
        string $elementName,
        string $property
    ): void {
        if (!$oaiRecord->{$property}) {
            return;
        }

        $element = new DOMElement($elementName);
        $element->nodeValue = $oaiRecord->{$property};

        $metadataElement->appendChild($element);
    }

}
