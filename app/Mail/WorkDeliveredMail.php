<?php

namespace App\Mail;

use App\Models\Work;

class WorkDeliveredMail extends BrandedMail
{
    public function __construct(public Work $work, public string $signedUrl)
    {
        $this->useLanguage($work->customer?->preferred_locale);
    }

    protected function messageData(): array
    {
        return [
            'subject' => $this->mailText('delivery_ready').' — '.$this->work->title,
            'heading' => $this->mailText('delivery_ready'),
            'name' => $this->work->customer->title,
            'intro' => $this->mailText('delivery_intro'),
            'fields' => [$this->mailText('project') => $this->work->title],
            'notes' => array_values(array_filter([filled($this->work->delivery_note) ? ['label' => $this->mailText('delivery_note'), 'text' => $this->work->delivery_note] : null, ['text' => $this->mailText('delivery_hint')]])),
            'actionUrl' => $this->signedUrl,
            'actionLabel' => $this->mailText('view_delivery'),
        ];
    }
}
