<?php

namespace Terraformers\OpenArchive\Formatters;

use DOMDocument;
use DOMElement;
use SilverStripe\Core\Injector\Injectable;
use Terraformers\OpenArchive\Models\OaiRecord;

abstract class OaiRecordFormatter
{

    use Injectable;

    abstract public function getMetadataPrefix(): string;

    abstract public function generateDomElement(
        DOMDocument $document,
        OaiRecord $oaiRecord,
        bool $includeMetadata = false
    ): DOMElement;

}
