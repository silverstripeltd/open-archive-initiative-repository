<?php

namespace Terraformers\OpenArchive\Controllers;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\SiteConfig\SiteConfig;
use Terraformers\OpenArchive\Documents\Errors\BadVerbDocument;
use Terraformers\OpenArchive\Documents\Errors\CannotDisseminateFormatDocument;
use Terraformers\OpenArchive\Documents\IdentifyDocument;
use Terraformers\OpenArchive\Documents\ListMetadataFormatsDocument;
use Terraformers\OpenArchive\Documents\ListRecordsDocument;
use Terraformers\OpenArchive\Documents\OaiDocument;
use Terraformers\OpenArchive\Formatters\OaiDcFormatter;
use Terraformers\OpenArchive\Formatters\OaiRecordFormatter;
use Terraformers\OpenArchive\Helpers\DateTimeHelper;
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
        OaiDocument::VERB_IDENTIFY,
        OaiDocument::VERB_LIST_METADATA_FORMATS,
        OaiDocument::VERB_LIST_RECORDS,
    ];

    private static array $supported_formats = [
        'oai_dc' => OaiDcFormatter::class,
    ];

    private static string $supportedProtocol = '2.0';

    private static string $supportedDeletedRecord = self::DELETED_SUPPORT_PERSISTENT;

    /**
     * All dates provided by the OAI repository must be ISO8601, and with an additional requirement that only "zulu" is
     * supported by the OAI spec (IE: YYYY-MM-DD, or YYYY-MM-DDTHH:MM:SSZ). The "Z" indicator means that we are using
     * "zulu" or "UTC+0" as the timezone
     *
     * @see http://www.openarchives.org/OAI/openarchivesprotocol.html#Dates
     */
    private static string $supportedGranularity = 'YYYY-MM-DDThh:mm:ssZ';

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
        $xmlDocument->setResponseDate();
        $xmlDocument->setRequestUrl($requestUrl);

        return $this->getResponseWithDocumentBody($xmlDocument);
    }

    protected function CannotDisseminateFormatResponse(HTTPRequest $request): HTTPResponse
    {
        $requestUrl = sprintf('%s%s', Director::absoluteBaseURL(), $request->getURL());

        $xmlDocument = CannotDisseminateFormatDocument::create();
        $xmlDocument->setResponseDate();
        $xmlDocument->setRequestUrl($requestUrl);

        return $this->getResponseWithDocumentBody($xmlDocument);
    }

    /**
     * The Identify verb contains important information about this data repository
     */
    protected function Identify(HTTPRequest $request): HTTPResponse
    {
        $xmlDocument = IdentifyDocument::create();

        // Response Date defaults to the time of the Request. Extension point is provided in this method
        $xmlDocument->setResponseDate();
        // Request URL defaults to the current URL. Extension point is provided in this method
        $xmlDocument->setRequestUrl($this->getRequestUrl($request));
        // Base URL defaults to the current URL. Extension point is provided in this method
        $xmlDocument->setBaseUrl($this->getBaseUrl($request));
        // Protocol Version defaults to 2.0. You can update the configuration if required
        $xmlDocument->setProtocolVersion($this->config()->get('supportedProtocol'));
        // Deleted Record support defaults to "persistent". You can update the configuration if required
        $xmlDocument->setDeletedRecord($this->config()->get('supportedDeletedRecord'));
        // Date Granularity support defaults to date and time. You can update the configuration if required
        $xmlDocument->setGranularity($this->config()->get('supportedGranularity'));
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
        $xmlDocument->setResponseDate();
        $xmlDocument->setRequestUrl($requestUrl);

        foreach (array_keys($this->config()->get('supported_formats')) as $metadataPrefix) {
            $formatter = $this->getOaiRecordFormatter($metadataPrefix);

            $xmlDocument->addSupportedFormatter($formatter);
        }

        return $this->getResponseWithDocumentBody($xmlDocument);
    }

    /**
     * Supported arguments
     * - metadataPrefix
     *
     * Upcoming supported arguments
     * - from
     * - until
     * - set
     * - resumptionToken
     */
    protected function ListRecords(HTTPRequest $request): HTTPResponse
    {
        // The metadataPrefix that records will be output in. Should match to one of our supported_formats
        $metadataPrefix = $request->getVar('metadataPrefix');

        if (!$metadataPrefix || !array_key_exists($metadataPrefix, $this->config()->get('supported_formats'))) {
            return $this->CannotDisseminateFormatResponse($request);
        }

        // The OaiRecord formatter that we're going to use
        $xmlDocument = ListRecordsDocument::create($this->getOaiRecordFormatter($metadataPrefix));
        // Response Date defaults to the time of the Request. Extension point is provided in this method
        $xmlDocument->setResponseDate();
        // Request URL defaults to the current URL. Extension point is provided in this method
        $xmlDocument->setRequestUrl($this->getRequestUrl($request));

        // The lower bound for selective harvesting
        $from = $request->getVar('from');
        // The upper bound for selective harvesting
        $until = $request->getVar('until');
        // Specifies the Set for selective harvesting
        $set = (int) $request->getVar('set');
        // An encoded string containing pagination requirements for selective harvesting
        $resumptionToken = $request->getVar('resumptionToken');

        if ($from) {
            try {
                $from = DateTimeHelper::getLocalStringFromUtc($from);
            } catch (Throwable $e) {
                $xmlDocument->addError(OaiDocument::ERROR_BAD_ARGUMENT, 'Invalid \'from\' date format provided');
            }
        }

        if ($until) {
            try {
                $until = DateTimeHelper::getLocalStringFromUtc($until);
            } catch (Throwable $e) {
                $xmlDocument->addError(OaiDocument::ERROR_BAD_ARGUMENT, 'Invalid \'until\' date format provided');
            }
        }

        if ($xmlDocument->hasErrors()) {
            return $this->getResponseWithDocumentBody($xmlDocument);
        }

        $oaiRecords = $this->fetchOaiRecords($from, $until, $set, $resumptionToken);

        if (!$oaiRecords->count()) {
            $xmlDocument->addError(OaiDocument::ERROR_NO_RECORDS_MATCH);

            return $this->getResponseWithDocumentBody($xmlDocument);
        }

        // Start processing whatever OaiRecords we found
        $xmlDocument->processOaiRecords($oaiRecords);

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
     * Regarding dates, please @see $supportedGranularity docblock. All dates passed to this method should already be
     * adjusted to local server time
     */
    protected function fetchOaiRecords(
        ?string $from = null,
        ?string $until = null,
        ?int $set = null,
        ?string $resumptionToken = null
    ): DataList {
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

        if ($resumptionToken) {
            // Resumption token support to be added
        }

        if (!$filters) {
            return OaiRecord::get();
        }

        return OaiRecord::get()->filter($filters);
    }

}
