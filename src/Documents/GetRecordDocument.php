<?php

namespace Terraformers\OpenArchive\Documents;

use Terraformers\OpenArchive\Formatters\OaiRecordFormatter;
use Terraformers\OpenArchive\Models\OaiRecord;

class GetRecordDocument extends OaiDocument
{

    private OaiRecordFormatter $formatter;

    public function __construct(OaiRecordFormatter $formatter)
    {
        parent::__construct();

        $this->formatter = $formatter;
        $this->setRequestVerb(OaiDocument::VERB_GET_RECORD);
        $this->setMetadataPrefix($this->formatter->getMetadataPrefix());
    }

    public function processOaiRecord(OaiRecord $oaiRecord): void
    {
        $rootElement = $this->findOrCreateElement('GetRecord');

        $rootElement->appendChild(
            $this->formatter->generateDomElement($this->document, $oaiRecord, true)
        );
    }

}
