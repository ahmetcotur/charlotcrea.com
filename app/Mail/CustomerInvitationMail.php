<?php

namespace App\Mail;

use App\Models\Customer;

class CustomerInvitationMail extends BrandedMail
{
    public function __construct(public Customer $customer, public string $inviteUrl)
    {
        $this->useLanguage($customer->preferred_locale);
    }

    protected function messageData(): array
    {
        return ['subject' => $this->mailText('invite_subject'), 'heading' => $this->mailText('invite_subject'), 'name' => $this->customer->title, 'intro' => $this->mailText('invite_intro'), 'fields' => [$this->mailText('email') => $this->customer->email], 'notes' => [['text' => $this->mailText('password_expiry')]], 'actionUrl' => $this->inviteUrl, 'actionLabel' => $this->mailText('activate')];
    }
}
