<?php

namespace App\Mail;

use App\Models\Quote;

class QuoteAcceptedMail extends BrandedMail
{
    public function __construct(public Quote $quote)
    {
        $this->useLanguage('tr');
    }

    protected function messageData(): array
    {
        return ['subject' => $this->mailText('quote_accepted').' — '.$this->quote->quote_number, 'heading' => $this->mailText('quote_accepted'), 'intro' => $this->mailText('accepted_intro', ['name' => $this->quote->customer->title]), 'fields' => [$this->mailText('quote_number') => $this->quote->quote_number, $this->mailText('project') => $this->quote->title, $this->mailText('total') => $this->money((float) $this->quote->amount).' '.$this->quote->currency, $this->mailText('accepted_at') => $this->quote->accepted_at?->format('d.m.Y H:i')], 'actionUrl' => route('admin.quotes.edit', $this->quote), 'actionLabel' => $this->mailText('admin_quote')];
    }
}
