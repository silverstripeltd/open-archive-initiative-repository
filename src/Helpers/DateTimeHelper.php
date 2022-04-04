<?php

namespace Terraformers\OpenArchive\Helpers;

use DateTime;
use DateTimeZone;
use Exception;

class DateTimeHelper
{

    public static function getLocalStringFromUtc(string $utcDateString): string
    {
        if (!static::isSupportedUtcFormat($utcDateString)) {
            throw new Exception('Invalid UTC date format provided');
        }

        // Note: strtotime() already converts UTC date strings (UTC+Z) into local timestamps
        return date('Y-m-d H:i:s', strtotime($utcDateString));
    }

    public static function getUtcStringFromLocal(string $localDateString): string
    {
        $dateTime = new DateTime($localDateString);
        $dateTime->setTimezone(new DateTimeZone('UTC'));

        return $dateTime->format('Y-m-d\TH:i:s\Z');
    }

    public static function isSupportedUtcFormat(string $dateString): bool
    {
        // Check to see if the date string matches the expected full ISO (date + time) format
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $dateString)) {
            return true;
        }

        // If it didn't match the full format, return if it matches the partial format
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString);
    }

}
