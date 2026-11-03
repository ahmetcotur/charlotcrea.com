<?php

namespace App\Mail;

use App\Models\Inquiry;

class InquiryReceiptMail extends BrandedMail
{
    public function __construct(public Inquiry $inquiry)
    {
        $this->useLanguage($inquiry->locale);
    }

    protected function messageData(): array
    {
        $quote = $this->inquiry->type === 'quote';
        $heading = $this->mailText($quote ? 'quote_received' : 'contact_received');

        return ['subject' => $heading.' — CC-INQ-'.str_pad((string) $this->inquiry->id, 5, '0', STR_PAD_LEFT), 'heading' => $heading, 'name' => $this->inquiry->name, 'intro' => $this->mailText($quote ? 'quote_receipt' : 'contact_receipt'), 'fields' => [$this->mailText('reference') => 'CC-INQ-'.str_pad((string) $this->inquiry->id, 5, '0', STR_PAD_LEFT), $this->mailText('project') => $this->inquiry->property, $this->mailText('service') => $this->inquiry->service], 'notes' => [['label' => $this->mailText('your_message'), 'text' => $this->inquiry->details], ['text' => $this->mailText('receipt_hint')]]];
    }
}
