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

    public function setBaseURL(string $url): void
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

    protected function getIdentifyElement(): DOMElement
    {
        return $this->findOrCreateElement('Identify');
    }

}
