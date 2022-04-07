<?php

namespace Terraformers\OpenArchive\Documents;

use Exception;
use SilverStripe\ORM\PaginatedList;
use Terraformers\OpenArchive\Formatters\OaiRecordFormatter;
use Terraformers\OpenArchive\Helpers\ResumptionTokenHelper;
use Terraformers\OpenArchive\Models\OaiRecord;

abstract class RecordsDocument extends OaiDocument
{

    protected ?OaiRecordFormatter $formatter = null;

    abstract public function shouldIncludeMetadata(): bool;

    abstract public function getRecordsHolderName(): string;

    public function __construct(?OaiRecordFormatter $formatter = null)
    {
        parent::__construct();

        if (!$formatter) {
            return;
        }

        $this->formatter = $formatter;
        $this->setMetadataPrefix($this->formatter->getMetadataPrefix());
    }

    public function setFormatter(OaiRecordFormatter $formatter): void
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

        $recordsHolderElement = $this->findOrCreateElement($this->getRecordsHolderName());

        foreach ($oaiRecords as $oaiRecord) {
            $recordsHolderElement->appendChild(
                $this->formatter->generateDomElement($this->document, $oaiRecord, $this->shouldIncludeMetadata())
            );
        }
    }

    public function setResumptionToken(string $resumptionToken): void
    {
        $recordsHolderElement = $this->findOrCreateElement($this->getRecordsHolderName());
        $resumptionTokenElement = $this->findOrCreateElement('resumptionToken', $recordsHolderElement);

        $resumptionTokenElement->nodeValue = $resumptionToken;

        $tokenExpiry = ResumptionTokenHelper::getExpiryFromResumptionToken($resumptionToken);

        if (!$tokenExpiry) {
            return;
        }

        $resumptionTokenElement->setAttribute('expirationDate', $tokenExpiry);
    }

}
