<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote, public string $signedUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Teklifiniz hazır — '.$this->quote->quote_number);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.quotes.sent');
    }
}
