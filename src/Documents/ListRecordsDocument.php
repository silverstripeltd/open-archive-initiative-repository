<?php

namespace Terraformers\OpenArchive\Documents;

use SilverStripe\ORM\PaginatedList;
use Terraformers\OpenArchive\Formatters\OaiRecordFormatter;
use Terraformers\OpenArchive\Helpers\ResumptionTokenHelper;
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
     * @param PaginatedList|OaiRecord[] $oaiRecords
     */
    public function processOaiRecords(PaginatedList $oaiRecords): void
    {
        $listRecordsElement = $this->findOrCreateElement('ListRecords');

        foreach ($oaiRecords as $oaiRecord) {
            $listRecordsElement->appendChild($this->formatter->generateDomElement($this->document, $oaiRecord, true));
        }
    }

    public function setResumptionToken(string $resumptionToken): void
    {
        $listRecordsElement = $this->findOrCreateElement('ListRecords');
        $resumptionTokenElement = $this->findOrCreateElement('resumptionToken', $listRecordsElement);

        $resumptionTokenElement->nodeValue = $resumptionToken;

        $tokenExpiry = ResumptionTokenHelper::getExpiryFromResumptionToken($resumptionToken);

        if (!$tokenExpiry) {
            return;
        }

        $resumptionTokenElement->setAttribute('expirationDate', $tokenExpiry);
    }

}
