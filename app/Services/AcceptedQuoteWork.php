<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\Work;
use Illuminate\Support\Facades\DB;

class AcceptedQuoteWork
{
    public function ensure(Quote $quote): ?Work
    {
        return DB::transaction(function () use ($quote) {
            $locked = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'accepted') {
                return null;
            }

            // Include archived jobs: accepting again must never recreate an existing job.
            $existing = Work::withTrashed()->where('quote_id', $locked->id)->oldest('id')->first();
            if ($existing) {
                return $existing;
            }

            return Work::create([
                'quote_id' => $locked->id,
                'customer_id' => $locked->customer_id,
                'title' => $locked->title,
                'brief' => $locked->scope,
                'status' => 'planned',
            ]);
        });
    }
}
