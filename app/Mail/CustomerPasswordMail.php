<?php

namespace App\Mail;

class CustomerPasswordMail extends BrandedMail
{
    public function __construct(public string $resetUrl, string $language = 'tr')
    {
        $this->useLanguage($language);
    }

    protected function messageData(): array
    {
        return ['subject' => $this->mailText('password_subject'), 'heading' => $this->mailText('password_subject'), 'intro' => $this->mailText('password_intro'), 'notes' => [['text' => $this->mailText('password_expiry')], ['text' => $this->mailText('password_ignore')]], 'actionUrl' => $this->resetUrl, 'actionLabel' => $this->mailText('password_action')];
    }
}
