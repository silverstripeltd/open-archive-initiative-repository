<?php

namespace Terraformers\OpenArchive\Documents;

use Terraformers\OpenArchive\Formatters\OaiRecordFormatter;

class ListIdentifiersDocument extends RecordsDocument
{

    public function __construct(?OaiRecordFormatter $formatter = null)
    {
        parent::__construct($formatter);

        $this->setRequestVerb(OaiDocument::VERB_LIST_IDENTIFIERS);
    }

    public function shouldIncludeMetadata(): bool
    {
        return false;
    }

    public function getRecordsHolderName(): string
    {
        return 'ListIdentifiers';
    }

}
