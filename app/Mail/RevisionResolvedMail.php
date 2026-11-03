<?php

namespace App\Mail;

use App\Models\RevisionRequest;

class RevisionResolvedMail extends BrandedMail
{
    public function __construct(public RevisionRequest $revisionRequest, public string $signedUrl)
    {
        $this->useLanguage($revisionRequest->work->customer?->preferred_locale);
    }

    protected function messageData(): array
    {
        $accepted = $this->revisionRequest->status === 'approved';
        $heading = $this->mailText($accepted ? 'revision_accepted' : 'revision_rejected');

        return ['subject' => $heading.' — '.$this->revisionRequest->work->title, 'heading' => $heading, 'name' => $this->revisionRequest->work->customer->title, 'intro' => $this->mailText($accepted ? 'revision_accepted_intro' : 'revision_rejected_intro'), 'fields' => [$this->mailText('project') => $this->revisionRequest->work->title], 'notes' => [['label' => $this->mailText('studio_response'), 'text' => $this->revisionRequest->admin_response]], 'actionUrl' => $this->signedUrl, 'actionLabel' => $this->mailText('view_work')];
    }
}
