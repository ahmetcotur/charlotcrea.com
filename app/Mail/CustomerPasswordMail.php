<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class CustomerPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $resetUrl, public string $language = 'tr') {}

    public function build()
    {
        return $this->subject($this->language === 'tr' ? 'Müşteri paneli · Parolanızı belirleyin' : 'Client portal · Set your password')->view('mail.customer-password');
    }
}
