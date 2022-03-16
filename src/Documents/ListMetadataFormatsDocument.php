<?php

namespace Terraformers\OpenArchive\Documents;

class ListMetadataFormatsDocument extends OaiDocument
{

    public function __construct()
    {
        parent::__construct();

        $this->setRequestVerb(OaiDocument::VERB_LIST_METADATA_FORMATS);
    }

}
