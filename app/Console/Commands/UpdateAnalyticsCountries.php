<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use MaxMind\Db\Reader;

class UpdateAnalyticsCountries extends Command
{
    protected $signature = 'charlot:analytics-countries {--month= : DB-IP release in YYYY-MM format}';

    protected $description = 'Download the DB-IP Country Lite database for local IP country lookups';

    public function handle(): int
    {
        $month = $this->option('month') ?: now()->format('Y-m');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $this->error('Use YYYY-MM for the release month.');

            return self::FAILURE;
        }
        $path = config('analytics.country_database');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0750, true);
        }
        $compressed = tempnam(dirname($path), 'country-download-');
        $temporary = tempnam(dirname($path), 'country-');
        try {
            $response = Http::timeout(120)->withOptions(['sink' => $compressed])->get('https://download.db-ip.com/free/dbip-country-lite-'.$month.'.mmdb.gz');
            $response->throw();
            $input = gzopen($compressed, 'rb');
            $output = fopen($temporary, 'wb');
            try {
                while (! gzeof($input)) {
                    fwrite($output, gzread($input, 1024 * 1024));
                }
            } finally {
                gzclose($input);
                fclose($output);
            }
            $reader = new Reader($temporary);
            $reader->metadata();
            $reader->close();
            chmod($temporary, 0640);
            rename($temporary, $path);
            $this->info('DB-IP Country Lite installed: '.$month.'. Attribution: https://db-ip.com');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Country database update failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($compressed);
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
