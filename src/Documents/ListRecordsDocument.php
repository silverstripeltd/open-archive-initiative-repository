<?php

namespace Terraformers\OpenArchive\Documents;

use SilverStripe\ORM\DataList;
use Terraformers\OpenArchive\Formatters\OaiRecordFormatter;
use Terraformers\OpenArchive\Models\OaiRecord;

class ListRecordsDocument extends OaiDocument
{

    private OaiRecordFormatter $formatter;

    public function __construct(OaiRecordFormatter $formatter)
    {
        parent::__construct();

        $this->formatter = $formatter;
        $this->setRequestVerb(OaiDocument::VERB_LIST_RECORDS);
        $this->setMetadataPrefix($this->formatter->getMetadataPrefix());
    }

    /**
     * @param DataList|OaiRecord[] $oaiRecords
     */
    public function processOaiRecords(DataList $oaiRecords): void
    {
        $listRecordsElement = $this->findOrCreateElement('ListRecords');

        foreach ($oaiRecords as $oaiRecord) {
            $listRecordsElement->appendChild($this->formatter->generateDomElement($oaiRecord, true));
        }
    }

}
