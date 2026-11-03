<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteDeclinedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Teklif reddedildi — '.$this->quote->quote_number);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.quotes.declined-admin');
    }
}
