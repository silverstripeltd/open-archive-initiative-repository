<?php

namespace Terraformers\OpenArchive\Documents;

use Exception;
use SilverStripe\ORM\PaginatedList;
use Terraformers\OpenArchive\Formatters\OaiRecordFormatter;
use Terraformers\OpenArchive\Helpers\ResumptionTokenHelper;
use Terraformers\OpenArchive\Models\OaiRecord;

class ListRecordsDocument extends OaiDocument
{

    private ?OaiRecordFormatter $formatter = null;

    public function __construct(?OaiRecordFormatter $formatter = null)
    {
        parent::__construct();

        if ($formatter) {
            $this->setMetadataPrefix($this->formatter->getMetadataPrefix());
            $this->formatter = $formatter;
        }

        $this->setRequestVerb(OaiDocument::VERB_LIST_RECORDS);
    }

    public function setFormatter(OaiRecordFormatter $formatter)
    {
        $this->formatter = $formatter;
        $this->setMetadataPrefix($this->formatter->getMetadataPrefix());
    }

    /**
     * @param PaginatedList|OaiRecord[] $oaiRecords
     */
    public function processOaiRecords(PaginatedList $oaiRecords): void
    {
        if (!$this->formatter) {
            throw new Exception('No OAI Record formatter has been set');
        }

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
