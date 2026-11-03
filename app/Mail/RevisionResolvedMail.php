<?php

namespace App\Mail;

use App\Models\RevisionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RevisionResolvedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public RevisionRequest $revisionRequest, public string $signedUrl)
    {
    }

    public function envelope(): Envelope
    {
        $label = $this->revisionRequest->status === 'approved' ? 'kabul edildi' : 'reddedildi';

        return new Envelope(subject: 'Revizyon talebiniz '.$label.' — '.$this->revisionRequest->work->title);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.works.revision-resolved');
    }
}
