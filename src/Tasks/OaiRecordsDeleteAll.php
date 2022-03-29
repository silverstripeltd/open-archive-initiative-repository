<?php

namespace Terraformers\OpenArchive\Tasks;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use Terraformers\OpenArchive\Models\OaiRecord;

class OaiRecordsDeleteAll extends BuildTask
{

    private static $segment = 'oai-records-delete-all'; // phpcs:ignore

    protected $title = 'OAI Records - Delete All'; // phpcs:ignore

    protected $description = 'Truncate the OaiRecord table'; // phpcs:ignore

    /**
     * @param HTTPRequest $request
     */
    public function run($request) // phpcs:ignore
    {
        DB::query(sprintf('truncate table %s', OaiRecord::config()->get('table_name')));

        echo 'Truncated OAI Records table';
    }

}
