<?php

namespace App\Services\Analytics;

use MaxMind\Db\Reader;

class CountryLookup
{
    public function lookup(string $ip): ?string
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }
        $path = config('analytics.country_database');
        if (! is_file($path)) {
            return null;
        }
        $reader = new Reader($path);
        try {
            $code = $reader->get($ip)['country']['iso_code'] ?? null;

            return is_string($code) && preg_match('/^[A-Z]{2}$/', $code) ? $code : null;
        } finally {
            $reader->close();
        }
    }
}
