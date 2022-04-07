<?php

namespace Terraformers\OpenArchive\Controllers;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\SiteConfig\SiteConfig;
use Terraformers\OpenArchive\Documents\Errors\BadVerbDocument;
use Terraformers\OpenArchive\Documents\Errors\CannotDisseminateFormatDocument;
use Terraformers\OpenArchive\Documents\GetRecordDocument;
use Terraformers\OpenArchive\Documents\IdentifyDocument;
use Terraformers\OpenArchive\Documents\ListIdentifiersDocument;
use Terraformers\OpenArchive\Documents\ListMetadataFormatsDocument;
use Terraformers\OpenArchive\Documents\ListRecordsDocument;
use Terraformers\OpenArchive\Documents\OaiDocument;
use Terraformers\OpenArchive\Documents\RecordsDocument;
use Terraformers\OpenArchive\Formatters\OaiDcFormatter;
use Terraformers\OpenArchive\Formatters\OaiRecordFormatter;
use Terraformers\OpenArchive\Helpers\DateTimeHelper;
use Terraformers\OpenArchive\Helpers\RecordIdentityHelper;
use Terraformers\OpenArchive\Helpers\ResumptionTokenHelper;
use Terraformers\OpenArchive\Models\OaiRecord;
use Throwable;

class OaiController extends Controller
{

    public const DELETED_SUPPORT_NO = 'no';
    public const DELETED_SUPPORT_PERSISTENT = 'persistent';
    public const DELETED_SUPPORT_TRANSIENT = 'transient';

    /**
     * Environment Variable that you should set for whatever you would like the admin email to be
     */
    public const OAI_API_ADMIN_EMAIL = 'OAI_API_ADMIN_EMAIL';

    /**
     * All requests to this endpoint should be routed through our index method
     */
    private static array $url_handlers = [
        '$@' => 'index',
    ];

    private static array $allowed_actions = [
        'index',
    ];

    private static array $supported_verbs = [
        OaiDocument::VERB_GET_RECORD,
        OaiDocument::VERB_IDENTIFY,
        OaiDocument::VERB_LIST_IDENTIFIERS,
        OaiDocument::VERB_LIST_METADATA_FORMATS,
        OaiDocument::VERB_LIST_RECORDS,
    ];

    private static array $supported_formats = [
        'oai_dc' => OaiDcFormatter::class,
    ];

    private static string $supported_protocol = '2.0';

    private static string $supported_deleted_record = self::DELETED_SUPPORT_PERSISTENT;

    /**
     * All dates provided by the OAI repository must be ISO8601, and with an additional requirement that only "zulu" is
     * supported by the OAI spec (IE: YYYY-MM-DD, or YYYY-MM-DDTHH:MM:SSZ). The "Z" indicator means that we are using
     * "zulu" or "UTC+0" as the timezone
     *
     * @see http://www.openarchives.org/OAI/openarchivesprotocol.html#Dates
     */
    private static string $supported_granularity = 'YYYY-MM-DDThh:mm:ssZ';

    /**
     * For verbs that use Resumption Tokens, this is the configuration that controls how many OAI Records we will load
     * into a single response
     */
    private static string $oai_records_per_page = '100';

    /**
     * The expiration time (in seconds) of any resumption tokens that are generated. Default is 60 minutes
     *
     * Set this to null if you want an infinite duration
     */
    private static ?int $resumption_token_expiry = 3600;

    public function index(HTTPRequest $request): HTTPResponse
    {
        $this->getResponse()->addHeader('Content-type', 'text/xml');

        $verb = $request->getVar('verb');

        if (!$verb || !in_array($verb, $this->config()->get('supported_verbs'), true)) {
            return $this->BadVerbResponse($request);
        }

        // Grab the Response object from the method matching the verb
        /** @var HTTPResponse $response */
        $response = $this->{$verb}($request);

        return $response;
    }

    protected function BadVerbResponse(HTTPRequest $request): HTTPResponse
    {
        $requestUrl = sprintf('%s%s', Director::absoluteBaseURL(), $request->getURL());

        $xmlDocument = BadVerbDocument::create();
        $xmlDocument->setRequestUrl($requestUrl);

        return $this->getResponseWithDocumentBody($xmlDocument);
    }

    protected function CannotDisseminateFormatResponse(HTTPRequest $request): HTTPResponse
    {
        $requestUrl = sprintf('%s%s', Director::absoluteBaseURL(), $request->getURL());

        $xmlDocument = CannotDisseminateFormatDocument::create();
        $xmlDocument->setRequestUrl($requestUrl);

        return $this->getResponseWithDocumentBody($xmlDocument);
    }

    /**
     * The Identify verb contains important information about this data repository
     */
    protected function Identify(HTTPRequest $request): HTTPResponse
    {
        $xmlDocument = IdentifyDocument::create();

        // Request URL defaults to the current URL. Extension point is provided in this method
        $xmlDocument->setRequestUrl($this->getRequestUrl($request));
        // Base URL defaults to the current URL. Extension point is provided in this method
        $xmlDocument->setBaseUrl($this->getBaseUrl($request));
        // Protocol Version defaults to 2.0. You can update the configuration if required
        $xmlDocument->setProtocolVersion($this->config()->get('supported_protocol'));
        // Deleted Record support defaults to "persistent". You can update the configuration if required
        $xmlDocument->setDeletedRecord($this->config()->get('supported_deleted_record'));
        // Date Granularity support defaults to date and time. You can update the configuration if required
        $xmlDocument->setGranularity($this->config()->get('supported_granularity'));
        // You should set your env var appropriately for this value
        $xmlDocument->setAdminEmail(Environment::getEnv(OaiController::OAI_API_ADMIN_EMAIL));
        // Earliest Datestamp defaults to the Jan 1970 (the start of UNIX). Extension point is provided in this method
        $xmlDocument->setEarliestDatestamp($this->getEarliestDatestamp());
        // Repository Name defaults to the Site name. Extension point is provided in this method
        $xmlDocument->setRepositoryName($this->getRepositoryName());
        // Domain can be edited through extension points provided. IDs are always just a number
        $xmlDocument->setOaiIdentifier(Director::host(), 1);

        return $this->getResponseWithDocumentBody($xmlDocument);
    }

    /**
     * This method has not been implemented yet. Atm it just returns an incredibly basic response with the Verb set
     */
    protected function ListMetadataFormats(HTTPRequest $request): HTTPResponse
    {
        $requestUrl = sprintf('%s%s', Director::absoluteBaseURL(), $request->getURL());

        $xmlDocument = ListMetadataFormatsDocument::create();
        $xmlDocument->setRequestUrl($requestUrl);

        foreach (array_keys($this->config()->get('supported_formats')) as $metadataPrefix) {
            $formatter = $this->getOaiRecordFormatter($metadataPrefix);

            $xmlDocument->addSupportedFormatter($formatter);
        }

        return $this->getResponseWithDocumentBody($xmlDocument);
    }

    protected function ListRecords(HTTPRequest $request): HTTPResponse
    {
        $xmlDocument = ListRecordsDocument::create();

        return $this->getRecordsResponse($request, $xmlDocument);
    }

    protected function ListIdentifiers(HTTPRequest $request): HTTPResponse
    {
        $xmlDocument = ListIdentifiersDocument::create();

        return $this->getRecordsResponse($request, $xmlDocument);
    }

    protected function GetRecord(HTTPRequest $request): HTTPResponse
    {
        // The metadataPrefix that records will be output in. Should match to one of our supported_formats
        $metadataPrefix = $request->getVar('metadataPrefix');
        $oaiIdentifier = $request->getVar('identifier');

        if (!$metadataPrefix || !array_key_exists($metadataPrefix, $this->config()->get('supported_formats'))) {
            return $this->CannotDisseminateFormatResponse($request);
        }

        $xmlDocument = GetRecordDocument::create($this->getOaiRecordFormatter($metadataPrefix));
        // Request URL defaults to the current URL. Extension point is provided in this method
        $xmlDocument->setRequestUrl($this->getRequestUrl($request));

        if (!$oaiIdentifier) {
            $xmlDocument->addError(OaiDocument::ERROR_BAD_ARGUMENT, 'Missing argument: \'identifier\'');

            // We cannot continue if we did not receive an identifier argument
            return $this->getResponseWithDocumentBody($xmlDocument);
        }

        $id = RecordIdentityHelper::getIdFromOaiIdentifier($oaiIdentifier);

        if (!$id) {
            $xmlDocument->addError(OaiDocument::ERROR_BAD_ARGUMENT, 'Invalid argument: \'identifier\'');

            // We cannot continue if the identifier argument is invalid
            return $this->getResponseWithDocumentBody($xmlDocument);
        }

        $oaiRecord = OaiRecord::get_by_id($id);

        if (!$oaiRecord || !$oaiRecord->exists()) {
            $xmlDocument->addError(OaiDocument::ERROR_ID_DOES_NOT_EXIST);

            // We cannot continue if we do not have a matching OAI Record
            return $this->getResponseWithDocumentBody($xmlDocument);
        }

        $xmlDocument->processOaiRecord($oaiRecord);

        return $this->getResponseWithDocumentBody($xmlDocument);
    }

    /**
     * Supported arguments
     * - metadataPrefix
     * - from
     * - until
     * - resumptionToken
     *
     * Upcoming supported arguments
     * - set
     */
    protected function getRecordsResponse(HTTPRequest $request, RecordsDocument $xmlDocument): HTTPResponse
    {
        // Request URL defaults to the current URL. Extension point is provided in this method
        $xmlDocument->setRequestUrl($this->getRequestUrl($request));

        // An encoded string containing request and pagination requirements for selective harvesting
        $resumptionToken = $request->getVar('resumptionToken');
        // The Record Formatter that we will use
        $metadataPrefix = null;
        // The current paginated page. Default is always 1
        $currentPage = 1;
        // The lower bound for selective harvesting. The original UTC should be preserved for Resumption Tokens and any
        // display requirements
        $fromUtc = null;
        // Local value which will be used purely for internal filtering
        $fromLocal = null;
        // The upper bound for selective harvesting. The original UTC should be preserved for Resumption Tokens and any
        // display requirements
        $untilUtc = null;
        // Local value which will be used purely for internal filtering
        $untilLocal = null;

        if ($resumptionToken) {
            // If we have a Resumption Token, then that needs to be where we define all of our request params from
            try {
                $resumptionParts = ResumptionTokenHelper::getRequestParamsFromResumptionToken($resumptionToken);

                $metadataPrefix = $resumptionParts['metadataPrefix'];
                $currentPage = $resumptionParts['page'];
                $fromUtc = $resumptionParts['from'] ?? null;
                $untilUtc = $resumptionParts['until'] ?? null;
                $set = $resumptionParts['set'] ?? null;
            } catch (Throwable $e) {
                $xmlDocument->addError(OaiDocument::ERROR_BAD_RESUMPTION_TOKEN, $e->getMessage());
            }
        } else {
            // If there is no Resumption Token, then any/all request params are expected to be provided through GET
            $metadataPrefix = $request->getVar('metadataPrefix');
            $fromUtc = $request->getVar('from');
            $untilUtc = $request->getVar('until');
            $set = $request->getVar('set');

            try {
                $fromLocal = DateTimeHelper::getLocalStringFromUtc($fromUtc);
            } catch (Throwable $e) {
                $xmlDocument->addError(OaiDocument::ERROR_BAD_ARGUMENT, 'Invalid \'from\' date format provided');
            }

            try {
                $untilLocal = DateTimeHelper::getLocalStringFromUtc($untilUtc);
            } catch (Throwable $e) {
                $xmlDocument->addError(OaiDocument::ERROR_BAD_ARGUMENT, 'Invalid \'until\' date format provided');
            }
        }

        // If the requested metadataPrefix does not match any of our Record Formatters then we cannot continue
        if (!$metadataPrefix || !array_key_exists($metadataPrefix, $this->config()->get('supported_formats'))) {
            return $this->CannotDisseminateFormatResponse($request);
        }

        // If we generated any errors above, then we should bail out there
        if ($xmlDocument->hasErrors()) {
            return $this->getResponseWithDocumentBody($xmlDocument);
        }

        $xmlDocument->setFormatter($this->getOaiRecordFormatter($metadataPrefix));

        // Grab the Paginated List of records based on our filter criteria
        $oaiRecords = $this->fetchOaiRecords($fromLocal, $untilLocal, $set);

        // Set the page length and current page of our Paginated list
        $oaiRecords->setPageLength($this->config()->get('oai_records_per_page'));
        $oaiRecords->setCurrentPage($currentPage);

        // If there are no results after we apply filters and pagination, then we should return an error response
        if (!$oaiRecords->Count()) {
            $xmlDocument->addError(OaiDocument::ERROR_NO_RECORDS_MATCH);

            return $this->getResponseWithDocumentBody($xmlDocument);
        }

        // Start processing whatever OaiRecords we found
        $xmlDocument->processOaiRecords($oaiRecords);

        // If there are still more records to be processed, then we need to add a new Resumption Token to our response
        if ($oaiRecords->TotalPages() > $currentPage) {
            $newResumptionToken = ResumptionTokenHelper::generateResumptionToken(
                $metadataPrefix,
                $currentPage + 1,
                $fromUtc,
                $untilUtc,
                $set
            );

            $xmlDocument->setResumptionToken($newResumptionToken);
        } elseif ($resumptionToken) {
            // If this is the last page of a request that included a Resumption Token, then we specifically need to add
            // an empty Token - indicating that the list is now complete
            $xmlDocument->setResumptionToken('');
        }

        return $this->getResponseWithDocumentBody($xmlDocument);
    }

    protected function getResponseWithDocumentBody(OaiDocument $xmlDocument): HTTPResponse
    {
        $this->getResponse()->setBody($xmlDocument->getDocumentBody());

        return $this->getResponse();
    }

    protected function getOaiRecordFormatter(string $metadataPrefix): OaiRecordFormatter
    {
        if (!array_key_exists($metadataPrefix, $this->config()->get('supported_formats'))) {
            throw new Exception(sprintf('Unsupported metadate prefix provided: %s', $metadataPrefix));
        }

        return Injector::inst()->create($this->config()->get('supported_formats')[$metadataPrefix]);
    }

    protected function getRequestUrl(HTTPRequest $request): string
    {
        $requestUrl = sprintf('%s%s', Director::absoluteBaseURL(), $request->getURL());

        $this->extend('updateOaiRequestUrl', $requestUrl);

        return $requestUrl;
    }

    protected function getBaseUrl(HTTPRequest $request): string
    {
        $baseUrl = sprintf('%s%s', Director::absoluteBaseURL(), $request->getURL());

        $this->extend('updateOaiBaseUrl', $baseUrl);

        return $baseUrl;
    }

    protected function getEarliestDatestamp(): string
    {
        // We're just going to set it to the start of the Unix timestamp (meaning, there could be any range of
        // datestamps in our system)
        $dateString = '1970-01-01T00:00:00Z';

        $this->extend('updateOaiEarliestDatestamp', $dateString);

        return $dateString;
    }

    protected function getRepositoryName(): string
    {
        $repositoryName = SiteConfig::current_site_config()->Title;

        $this->extend('updateOaiRepositoryName', $repositoryName);

        return $repositoryName;
    }

    /**
     * Regarding dates, please @see $supported_granularity docblock. All dates passed to this method should already be
     * adjusted to local server time
     */
    protected function fetchOaiRecords(?string $from = null, ?string $until = null, ?int $set = null): PaginatedList
    {
        $filters = [];

        if ($from) {
            $filters['LastEdited:GreaterThanOrEqual'] = $from;
        }

        if ($until) {
            $filters['LastEdited:LessThanOrEqual'] = $until;
        }

        if ($set) {
            // Set support to be added
        }

        if (!$filters) {
            return PaginatedList::create(OaiRecord::get());
        }

        $list = OaiRecord::get()
            ->sort('LastEdited ASC')
            ->filter($filters);

        return PaginatedList::create($list);
    }

}
