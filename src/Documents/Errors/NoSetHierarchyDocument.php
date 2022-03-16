<?php

namespace Terraformers\OpenArchive\Documents\Errors;

use Terraformers\OpenArchive\Documents\OaiDocument;

class NoSetHierarchyDocument extends OaiDocument
{

    public function __construct()
    {
        parent::__construct();

        $this->addError(OaiDocument::ERROR_NO_SET_HIERARCHY);
    }

}
