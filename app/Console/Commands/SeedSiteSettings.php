<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Services\SiteSettings;
use Illuminate\Console\Command;

class SeedSiteSettings extends Command
{
    protected $signature = 'charlot:seed-settings';

    protected $description = 'Initialize shared contact settings without replacing saved values';

    public function handle(SiteSettings $settings): int
    {
        SiteSetting::firstOrCreate(['id' => 1], array_intersect_key($settings->defaults(), array_flip((new SiteSetting)->getFillable())));
        $this->info('Shared site settings are ready. Existing values were preserved.');

        return self::SUCCESS;
    }
}
