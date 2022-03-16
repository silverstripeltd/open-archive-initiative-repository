<?php

namespace Terraformers\OpenArchive\Controllers;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\SiteConfig\SiteConfig;
use Terraformers\OpenArchive\Documents\Errors\BadVerbDocument;
use Terraformers\OpenArchive\Documents\IdentifyDocument;
use Terraformers\OpenArchive\Documents\ListMetadataFormatsDocument;
use Terraformers\OpenArchive\Documents\ListRecordsDocument;
use Terraformers\OpenArchive\Documents\OaiDocument;

class OaiController extends Controller
{

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

    private static string $supportedProtocol = '2.0';

    private static string $supportedDeletedRecord = 'persistent';

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

        $this->getResponse()->setBody($xmlDocument->getDocumentBody());

        return $this->getResponse();
    }

    /**
     * This method has not been implemented yet. Atm it just returns an incredibly basic response with the Verb set
     */
    protected function Identify(HTTPRequest $request): HTTPResponse
    {
        $adminEmail = Environment::getEnv(static::OAI_API_ADMIN_EMAIL);

        if (!$adminEmail) {
            throw new Exception('Administrator email (environment variable) must be set');
        }

        $requestUrl = sprintf('%s%s', Director::absoluteBaseURL(), $request->getURL());

        $this->extend('updateOaiRequestUrl', $requestUrl);

        $xmlDocument = IdentifyDocument::create();
        $xmlDocument->setResponseDate($this->getResponseDate());
        $xmlDocument->setRequestUrl($requestUrl);
        $xmlDocument->setBaseURL($requestUrl);
        $xmlDocument->setProtocolVersion($this->config()->get('supportedProtocol'));
        $xmlDocument->setDeletedRecord($this->config()->get('supportedDeletedRecord'));
        $xmlDocument->setGranularity($this->config()->get('supportedGranularity'));
        $xmlDocument->setAdminEmail($adminEmail);
        $xmlDocument->setEarliestDatestamp($this->getEarliestDatestamp());
        $xmlDocument->setRepositoryName($this->getRepositoryName());

        $this->getResponse()->setBody($xmlDocument->getDocumentBody());

        return $this->getResponse();
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
        $xmlDocument->setRequestSpec('oai_dc');

        $this->getResponse()->setBody($xmlDocument->getDocumentBody());

        return $this->getResponse();
    }

    /**
     * This method has not been implemented yet. Atm it just returns an incredibly basic response with the Verb set
     */
    protected function ListRecords(HTTPRequest $request): HTTPResponse
    {
        $requestUrl = sprintf('%s%s', Director::absoluteBaseURL(), $request->getURL());

        $xmlDocument = ListRecordsDocument::create();
        $xmlDocument->setResponseDate();
        $xmlDocument->setRequestUrl($requestUrl);
        $xmlDocument->setRequestSpec('oai_dc');

        $this->getResponse()->setBody($xmlDocument->getDocumentBody());

        return $this->getResponse();
    }

    protected function getResponseDate(): int
    {
        $timestamp = DBDatetime::now()->getTimestamp();

        $this->extend('updateOaiResponseDate', $timestamp);

        return $timestamp;
    }

    protected function getEarliestDatestamp(): int
    {
        $timestamp = 0;

        $this->extend('updateOaiEarliestDatestamp', $timestamp);

        return $timestamp;
    }

    protected function getRepositoryName(): string
    {
        $repositoryName = SiteConfig::current_site_config()->Title;

        $this->extend('updateOaiRepositoryName', $repositoryName);

        return $repositoryName;
    }

}
