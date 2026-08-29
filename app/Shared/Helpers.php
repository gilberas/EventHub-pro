<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

if (! function_exists('date_format_sql')) {
    /**
     * Returns the database-appropriate date format SQL fragment.
     * MySQL: DATE_FORMAT(column, '%Y-%m')
     * SQLite: strftime('%Y-%m', column)
     */
    function date_format_sql(string $column, string $format = '%Y-%m'): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('{$format}', {$column})"
            : "DATE_FORMAT({$column}, '{$format}')";
    }
}

if (! function_exists('safe_intended_url')) {
    /**
     * Validates a client-supplied post-auth redirect target.
     *
     * Only returns the URL when it points to the same application host so we
     * never send a user to an external/open-redirect destination. Returns null
     * for anything else so callers can fall back to their default path.
     */
    function safe_intended_url(?string $intended): ?string
    {
        if ($intended === null || $intended === '') {
            return null;
        }

        if (str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            return $intended;
        }

        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $targetHost = (string) parse_url($intended, PHP_URL_HOST);

        return ($targetHost !== '' && strcasecmp($targetHost, $host) === 0) ? $intended : null;
    }
}
