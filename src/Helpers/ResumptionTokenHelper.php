<?php

namespace Terraformers\OpenArchive\Helpers;

use Exception;
use SilverStripe\ORM\FieldType\DBDatetime;
use Terraformers\OpenArchive\Controllers\OaiController;

/**
 * Resumption Tokens are a form of pagination, but they also include any/all filter criteria for the request.
 *
 * The goal is that a Harvester might make an initial request for something like:
 * ?verb=ListRecords&metadataFormat=oai_dc&from=2022-01-01&until=2022-02-01
 *
 * If we then determine that there are additional pages of records that the Harvester needs to consumer, then we would
 * present them with a Resumption Token (for us, that's a base64 encoded value). The Harvester would then use the
 * verb and resumption token to perform followup requests:
 * ?verb=ListRecords&resumptionToken=[some-token-we-generated]
 *
 * Resumption Tokens can also come with an expiry date to serve as a form of validation to check that requests are being
 * completed within a particular timeframe. Expiry is enabled by default, but you can disable it by updating setting
 * the OaiController::$resumption_token_expiry config to null.
 */
class ResumptionTokenHelper
{

    public static function generateResumptionToken(
        string $metadataPrefix,
        int $page,
        ?string $from = null,
        ?string $until = null,
        ?int $set = null
    ): string {
        // Every Resumption Token must include a metadataPrefix and page
        $parts = [
            'metadataPrefix' => $metadataPrefix,
            'page' => $page,
        ];

        // Check to see if we want to give our Tokens an expiry date
        $tokenExpiryLength = OaiController::config()->get('resumption_token_expiry');

        if ($tokenExpiryLength) {
            // Set the expiry date for a time in the future matching the expiry length
            $parts['expiry'] = DateTimeHelper::getUtcStringFromLocal(
                date('Y-m-d H:i:s', DBDatetime::now()->getTimestamp() + $tokenExpiryLength)
            );
        }

        if ($from) {
            $parts['from'] = $from;
        }

        if ($until) {
            $parts['until'] = $until;
        }

        if ($set) {
            $parts['set'] = $set;
        }

        return base64_encode(json_encode($parts));
    }

    public static function getRequestParamsFromResumptionToken(string $resumptionToken): array
    {
        $resumptionParts = static::getResumptionTokenParts($resumptionToken);

        // Grab the array values of our Resumption Token or default those values to null
        $resumptionPage = $resumptionParts['page'] ?? null;
        $resumptionMetadataPrefix = $resumptionParts['metadataPrefix'] ?? null;
        $resumptionExpiry = $resumptionParts['expiry'] ?? null;

        // Every Resumption Token should include (at the very least) the active page and the metadataPrefix, if it
        // doesn't, then it's invalid
        if (!$resumptionPage || !$resumptionMetadataPrefix) {
            throw new Exception('Invalid resumption token');
        }

        // The duration that each Token lives (in seconds)
        $tokenExpiryLength = OaiController::config()->get('resumption_token_expiry');

        // The duration has been set to infinite, so we can return now
        if (!$tokenExpiryLength) {
            return $resumptionParts;
        }

        // If the current time is greater than the expiry date of the Resumption Token, then this Token is invalid
        // Note: strtotime() already converts UTC date strings (UTC+Z) into local timestamps
        if (DBDatetime::now()->getTimestamp() > strtotime($resumptionExpiry)) {
            throw new Exception('Invalid resumption token');
        }

        // The Resumption Token is valid, so we can return whatever value we have for page
        return $resumptionParts;
    }

    public static function getExpiryFromResumptionToken(string $resumptionToken): ?string
    {
        $resumptionParts = static::getResumptionTokenParts($resumptionToken);

        return $resumptionParts['expiry'] ?? null;
    }

    protected static function getResumptionTokenParts(string $resumptionToken): array
    {
        if (!$resumptionToken) {
            return [];
        }

        $decode = base64_decode($resumptionToken, true);

        // We can't do anything with an invalid encoded value
        if (!$decode) {
            throw new Exception('Invalid resumption token');
        }

        $resumptionParts = json_decode($decode, true);

        // We expect all Resumption Tokens to decode to an array
        if (!is_array($resumptionParts)) {
            throw new Exception('Invalid resumption token');
        }

        return $resumptionParts;
    }

}
