<?php

namespace App\Observers;

use App\Models\Quote;
use App\Services\AcceptedQuoteWork;
use App\Observers\Concerns\ResolvesChangedBy;

class QuoteObserver
{
    use ResolvesChangedBy;

    public function creating(Quote $quote): void
    {
        $this->stampStatus($quote);
        $quote->published = $quote->status !== 'draft';
    }

    public function updating(Quote $quote): void
    {
        if ($quote->isDirty('status')) {
            $this->stampStatus($quote);
            $quote->published = $quote->status !== 'draft';
        }
    }

    public function created(Quote $quote): void
    {
        $this->record($quote, null, $quote->status);
        if ($quote->status === 'accepted') {
            app(AcceptedQuoteWork::class)->ensure($quote);
        }
    }

    public function updated(Quote $quote): void
    {
        if ($quote->wasChanged('status')) {
            $this->record($quote, $quote->getOriginal('status'), $quote->status);
            if ($quote->status === 'accepted') {
                app(AcceptedQuoteWork::class)->ensure($quote);
            }
        }
    }

    private function stampStatus(Quote $quote): void
    {
        if ($quote->status === 'sent' && ! $quote->sent_at) {
            $quote->sent_at = now();
        }

        if ($quote->status === 'accepted' && ! $quote->accepted_at) {
            $quote->accepted_at = now();
        }
    }

    private function record(Quote $quote, ?string $from, string $to): void
    {
        $quote->statusEvents()->create([
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $this->resolveChangedBy(),
            'note' => $this->customerActionNote(),
        ]);
    }
}
