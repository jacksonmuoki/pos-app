<?php

namespace App\Support;

use Carbon\Carbon;

class Fmt
{
    public static function money(float|int|null $value): string
    {
        return 'KES ' . number_format((float) $value, 2);
    }

    public static function bytes(float|int|null $bytes): string
    {
        $bytes = (float) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }

    public static function ago($date): string
    {
        return $date ? Carbon::parse($date)->diffForHumans() : 'never';
    }

    /** Build a company URL that keeps the scheme and port of the current request. */
    public static function url(string $domain, string $path = ''): string
    {
        $request = request();
        $scheme = $request->getScheme();
        $port = $request->getPort();
        $isDefault = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);

        return $scheme . '://' . $domain . ($isDefault ? '' : ':' . $port) . $path;
    }
}