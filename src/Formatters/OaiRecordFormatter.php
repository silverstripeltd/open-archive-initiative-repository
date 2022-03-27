<?php

namespace Terraformers\OpenArchive\Formatters;

use DOMDocument;
use DOMElement;
use SilverStripe\Core\Injector\Injectable;
use Terraformers\OpenArchive\Models\OaiRecord;

abstract class OaiRecordFormatter
{

    use Injectable;

    /**
     * Value used for the metadataPrefix field
     */
    abstract public function getMetadataPrefix(): string;

    /**
     * Value used for the metadataNamespace field
     */
    abstract public function getMetadataNamespaceUrl(): string;

    /**
     * Value used for the schema field
     */
    abstract public function getSchemaUrl(): string;

    /**
     * The active DOMDocument must be passed to our Formatter. DOMElements must be created through the DOMDocument
     * if we want them to be editable (eg: have the ability to have child elements appended)
     */
    abstract public function generateDomElement(
        DOMDocument $document,
        OaiRecord $oaiRecord,
        bool $includeMetadata = false
    ): DOMElement;

}
