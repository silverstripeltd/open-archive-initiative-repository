<?php

namespace Terraformers\OpenArchive\Documents;

use DOMElement;

class IdentifyDocument extends OaiDocument
{

    public function __construct()
    {
        parent::__construct();

        $this->setRequestVerb(OaiDocument::VERB_IDENTIFY);
    }

    public function setRepositoryName(string $name): void
    {
        $domElement = $this->findOrCreateElement('repositoryName', $this->getIdentifyElement());
        $domElement->nodeValue = $name;
    }

    public function setBaseUrl(string $url): void
    {
        $domElement = $this->findOrCreateElement('baseURL', $this->getIdentifyElement());
        $domElement->nodeValue = $url;
    }

    public function setProtocolVersion(string $version): void
    {
        $domElement = $this->findOrCreateElement('protocolVersion', $this->getIdentifyElement());
        $domElement->nodeValue = $version;
    }

    public function setAdminEmail(string $email): void
    {
        $domElement = $this->findOrCreateElement('adminEmail', $this->getIdentifyElement());
        $domElement->nodeValue = $email;
    }

    public function setEarliestDatestamp(int $timestamp): void
    {
        $domElement = $this->findOrCreateElement('earliestDatestamp', $this->getIdentifyElement());
        $domElement->nodeValue = date('Y-m-d\Th:i:s\Z', $timestamp);
    }

    public function setDeletedRecord(string $supportLevel): void
    {
        $domElement = $this->findOrCreateElement('deletedRecord', $this->getIdentifyElement());
        $domElement->nodeValue = $supportLevel;
    }

    public function setGranularity(string $granularityFormat): void
    {
        $domElement = $this->findOrCreateElement('granularity', $this->getIdentifyElement());
        $domElement->nodeValue = $granularityFormat;
    }

    /**
     * @param string $domain
     * @param string|int $sampleId
     * @return void
     */
    public function setOaiIdentifier(string $domain, $sampleId): void
    {
        $descriptionElement = $this->findOrCreateElement('description', $this->getIdentifyElement());

        $identifierElement = $this->findOrCreateElement('oai-identifier', $descriptionElement);
        $identifierElement->setAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/oai-identifier');
        $identifierElement->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $identifierElement->setAttribute(
            'xsi:schemaLocation',
            'http://www.openarchives.org/OAI/2.0/oai-identifier http://www.openarchives.org/OAI/2.0/oai-identifier.xsd'
        );

        $schemeElement = $this->findOrCreateElement('scheme', $identifierElement);
        $schemeElement->nodeValue = 'oai';

        $delimiterElement = $this->findOrCreateElement('delimiter', $identifierElement);
        $delimiterElement->nodeValue = ':';

        $repoIdentifierElement = $this->findOrCreateElement('repositoryIdentifier', $identifierElement);
        $repoIdentifierElement->nodeValue = $domain;

        $sampleIdentifierElement = $this->findOrCreateElement('sampleIdentifier', $identifierElement);
        $sampleIdentifierElement->nodeValue = sprintf('oai:%s:%s', $domain, $sampleId);
    }

    protected function getIdentifyElement(): DOMElement
    {
        return $this->findOrCreateElement('Identify');
    }

}
