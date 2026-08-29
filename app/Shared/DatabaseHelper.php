<?php

declare(strict_types=1);

namespace App\Shared;

use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    /**
     * Returns a DB::raw expression for date formatting that works across MySQL and SQLite.
     */
    public static function dateFormat(string $column, string $format = '%Y-%m'): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "strftime('{$format}', {$column})";
        }

        return "DATE_FORMAT({$column}, '{$format}')";
    }
}
