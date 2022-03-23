<?php

namespace Terraformers\OpenArchive\Documents;

use Terraformers\OpenArchive\Formatters\OaiRecordFormatter;

class ListMetadataFormatsDocument extends OaiDocument
{

    public function __construct()
    {
        parent::__construct();

        $this->setRequestVerb(OaiDocument::VERB_LIST_METADATA_FORMATS);
    }

    public function addSupportedFormatter(OaiRecordFormatter $formatter): void
    {
        $listFormatsElement = $this->findOrCreateElement('ListMetadataFormats');

        $formatElement = $this->document->createElement('metadataFormat');

        $listFormatsElement->appendChild($formatElement);

        $prefixElement = $this->document->createElement('metadataPrefix');
        $prefixElement->nodeValue = $formatter->getMetadataPrefix();

        $formatElement->appendChild($prefixElement);

        $schemaElement = $this->document->createElement('schema');
        $schemaElement->nodeValue = $formatter->getSchemaUrl();

        $formatElement->appendChild($schemaElement);

        $namespaceElement = $this->document->createElement('metadataNamespace');
        $namespaceElement->nodeValue = $formatter->getMetadataNamespaceUrl();

        $formatElement->appendChild($namespaceElement);
    }

}
