<?php

namespace App\Mail;

use App\Models\Quote;

class QuoteSentMail extends BrandedMail
{
    public function __construct(public Quote $quote, public string $signedUrl)
    {
        $this->useLanguage($quote->customer?->preferred_locale);
    }

    protected function messageData(): array
    {
        return [
            'subject' => $this->mailText('quote_ready').' — '.$this->quote->quote_number,
            'heading' => $this->mailText('quote_ready'),
            'name' => $this->quote->customer->title,
            'intro' => $this->mailText('quote_intro'),
            'fields' => [$this->mailText('quote_number') => $this->quote->quote_number, $this->mailText('project') => $this->quote->title, $this->mailText('total') => $this->money((float) $this->quote->amount).' '.$this->quote->currency, $this->mailText('valid_until') => $this->quote->valid_until?->format('d.m.Y')],
            'items' => $this->quote->items->map(fn ($item) => ['title' => $item->title, 'description' => $item->description, 'quantity' => rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.'), 'amount' => $this->money($item->line_total).' '.$this->quote->currency])->all(),
            'notes' => [['text' => $this->mailText('quote_note')]],
            'actionUrl' => $this->signedUrl,
            'actionLabel' => $this->mailText('view_quote'),
        ];
    }
}
