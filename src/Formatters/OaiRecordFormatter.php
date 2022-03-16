<?php

namespace Terraformers\OpenArchive\Formatters;

use Terraformers\OpenArchive\Models\OaiRecord;
use DOMElement;

abstract class OaiRecordFormatter
{

    abstract public function generateDomElement(OaiRecord $record): DOMElement;

}
