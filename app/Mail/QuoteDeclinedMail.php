<?php

namespace App\Mail;

use App\Models\Quote;

class QuoteDeclinedMail extends BrandedMail
{
    public function __construct(public Quote $quote)
    {
        $this->useLanguage('tr');
    }

    protected function messageData(): array
    {
        return ['subject' => $this->mailText('quote_declined').' — '.$this->quote->quote_number, 'heading' => $this->mailText('quote_declined'), 'intro' => $this->mailText('declined_intro', ['name' => $this->quote->customer->title]), 'fields' => [$this->mailText('quote_number') => $this->quote->quote_number, $this->mailText('project') => $this->quote->title], 'actionUrl' => route('admin.quotes.edit', $this->quote), 'actionLabel' => $this->mailText('admin_quote')];
    }
}
