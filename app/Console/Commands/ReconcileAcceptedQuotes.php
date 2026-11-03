<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Models\Work;
use App\Services\AcceptedQuoteWork;
use Illuminate\Console\Command;

class ReconcileAcceptedQuotes extends Command
{
    protected $signature = 'charlot:reconcile-accepted-quotes {--apply : Create missing planned jobs; without this option only report}';

    protected $description = 'Find accepted quotes without a linked job, including archived jobs';

    public function handle(AcceptedQuoteWork $service): int
    {
        $missing = Quote::where('status', 'accepted')->whereNotIn('id', Work::withTrashed()->whereNotNull('quote_id')->select('quote_id'))->get();
        $this->info($missing->count().' accepted quotes have no linked job.');
        foreach ($missing as $quote) {
            $this->line($quote->quote_number.' · '.$quote->title);
            if ($this->option('apply')) {
                $service->ensure($quote);
            }
        }

        return self::SUCCESS;
    }
}
