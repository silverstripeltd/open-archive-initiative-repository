<?php

namespace Terraformers\OpenArchive\Formatters;

use DOMElement;
use Terraformers\OpenArchive\Models\OaiRecord;

abstract class OaiRecordFormatter
{

    abstract public function generateDomElement(OaiRecord $record): DOMElement;

}
