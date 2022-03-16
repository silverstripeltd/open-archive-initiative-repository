<?php

namespace Terraformers\OpenArchive\Documents\Errors;

use Terraformers\OpenArchive\Documents\OaiDocument;

class CannotDisseminateFormatDocument extends OaiDocument
{

    public function __construct()
    {
        parent::__construct();

        $this->addError(OaiDocument::ERROR_CANNOT_DISSEMINATE_FORMAT);
    }

}
