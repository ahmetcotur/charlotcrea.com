<?php

namespace App\Console\Commands;

use App\Models\PageVisit;
use Illuminate\Console\Command;

class PruneAnalytics extends Command
{
    protected $signature = 'charlot:analytics-prune';

    protected $description = 'Delete visitor records older than the analytics retention period';

    public function handle(): int
    {
        $cutoff = now()->subDays(max(1, config('analytics.retention_days')));
        $deleted = 0;
        do {
            $ids = PageVisit::where('visited_at', '<', $cutoff)->limit(1000)->pluck('id');
            $deleted += PageVisit::whereIn('id', $ids)->delete();
        } while ($ids->count() === 1000);
        $this->info('Deleted '.$deleted.' expired visitor records.');

        return self::SUCCESS;
    }
}
