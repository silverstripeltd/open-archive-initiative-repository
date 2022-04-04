<?php

namespace Terraformers\OpenArchive\Helpers;

use Exception;
use SilverStripe\ORM\FieldType\DBDatetime;
use Terraformers\OpenArchive\Controllers\OaiController;

/**
 * Resumption Tokens are a form of pagination, however, they also contain a level of validation.
 *
 * Each Resumption Token should represent a specific request, including whatever filters might have been applied as
 * part of that request, as well as representing a particular "page" in the Paginated List.
 *
 * The goal is to increase reliability of pagination by making sure that each requested "page" came from a request
 * containing the expected filters. EG: You can't send an unfiltered request for OAI Records, see that there are 10
 * pages, and then decide to request page=10 with some filters now applied. The Token itself would be aware that a
 * different filter has been applied, and it would be invalid.
 */
class ResumptionTokenHelper
{

    public static function generateResumptionToken(
        string $verb,
        int $page,
        ?string $from = null,
        ?string $until = null,
        ?int $set = null
    ): string {
        // Every Resumption Token must include a verb and page
        $parts = [
            'page' => $page,
            'verb' => $verb,
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

    public static function getPageFromResumptionToken(
        string $resumptionToken,
        string $expectedVerb,
        ?string $expectedFrom = null,
        ?string $expectedUntil = null,
        ?int $expectedSet = null
    ): int {
        $resumptionParts = static::getResumptionTokenParts($resumptionToken);

        // Grab the array values of our Resumption Token or default those values to null
        $resumptionPage = $resumptionParts['page'] ?? null;
        $resumptionVerb = $resumptionParts['verb'] ?? null;
        $resumptionFrom = $resumptionParts['from'] ?? null;
        $resumptionUntil = $resumptionParts['until'] ?? null;
        $resumptionSet = $resumptionParts['set'] ?? null;
        $resumptionExpiry = $resumptionParts['expiry'] ?? null;

        // Every Resumption Token should include (at the very least) the active page, if it doesn't, then it's invalid
        if (!$resumptionPage) {
            throw new Exception('Invalid resumption token');
        }

        // If any of these values do not match the expected values, then this Resumption Token is invalid
        if ($resumptionVerb !== $expectedVerb
            || $resumptionFrom !== $expectedFrom
            || $resumptionUntil !== $expectedUntil
            || $resumptionSet !== $expectedSet
        ) {
            throw new Exception('Invalid resumption token');
        }

        // The duration that each Token lives (in seconds)
        $tokenExpiryLength = OaiController::config()->get('resumption_token_expiry');

        // The duration has been set to infinite, so we can return now
        if (!$tokenExpiryLength) {
            return $resumptionPage;
        }

        // If the current time is greater than the expiry date of the Resumption Token, then this Token is invalid
        // Note: strtotime() already converts UTC date strings (UTC+Z) into local timestamps
        if (DBDatetime::now()->getTimestamp() > strtotime($resumptionExpiry)) {
            throw new Exception('Invalid resumption token');
        }

        // The Resumption Token is valid, so we can return whatever value we have for page
        return $resumptionPage;
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
