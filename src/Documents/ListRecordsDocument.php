<?php

namespace Terraformers\OpenArchive\Documents;

class ListRecordsDocument extends OaiDocument
{

    public function __construct()
    {
        parent::__construct();

        $this->setRequestVerb(OaiDocument::VERB_LIST_RECORDS);
    }

}
