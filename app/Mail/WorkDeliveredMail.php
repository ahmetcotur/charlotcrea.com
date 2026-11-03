<?php

namespace App\Mail;

use App\Models\Work;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkDeliveredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Work $work, public string $signedUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'İşiniz teslim edildi — '.$this->work->title);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.works.delivered');
    }
}
