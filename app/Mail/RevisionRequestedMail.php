<?php

namespace App\Mail;

use App\Models\RevisionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RevisionRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public RevisionRequest $revisionRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Revizyon talebi — '.$this->revisionRequest->work->title);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.works.revision-requested-admin');
    }
}
