<?php

namespace Terraformers\OpenArchive\Documents\Errors;

use Terraformers\OpenArchive\Documents\OaiDocument;

class BadArgumentDocument extends OaiDocument
{

    public function __construct()
    {
        parent::__construct();

        $this->addError(OaiDocument::ERROR_BAD_ARGUMENT);
    }

}
