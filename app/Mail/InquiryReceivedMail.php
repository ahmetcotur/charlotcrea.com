<?php

namespace App\Mail;

use App\Models\Inquiry;

class InquiryReceivedMail extends BrandedMail
{
    public function __construct(public Inquiry $inquiry)
    {
        $this->useLanguage('tr');
    }

    protected function messageData(): array
    {
        $heading = $this->mailText($this->inquiry->type === 'quote' ? 'new_quote' : 'new_contact');
        $fields = [$this->mailText('reference') => 'CC-INQ-'.str_pad((string) $this->inquiry->id, 5, '0', STR_PAD_LEFT), $this->mailText('customer') => $this->inquiry->name, $this->mailText('email') => $this->inquiry->email];
        foreach (['property' => 'project', 'phone' => 'phone', 'service' => 'service', 'project_type' => 'project_type', 'budget' => 'budget', 'usage' => 'usage', 'deliverables' => 'deliverables', 'timing' => 'timing'] as $field => $label) {
            $fields[$this->mailText($label)] = $this->inquiry->$field;
        }
        $fields[$this->mailText('location')] = collect([$this->inquiry->city, $this->inquiry->country])->filter()->join(', ');

        return ['subject' => $heading.' — '.$this->inquiry->name, 'heading' => $heading, 'intro' => $this->mailText('admin_inquiry_intro'), 'fields' => $fields, 'notes' => [['label' => $this->mailText('your_message'), 'text' => $this->inquiry->details]], 'actionUrl' => route('admin.inquiries.edit', $this->inquiry), 'actionLabel' => $this->mailText('view_inquiry'), 'replyAddress' => $this->inquiry->email];
    }
}
