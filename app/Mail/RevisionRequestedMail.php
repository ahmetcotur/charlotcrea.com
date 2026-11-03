<?php

namespace App\Mail;

use App\Models\RevisionRequest;

class RevisionRequestedMail extends BrandedMail
{
    public function __construct(public RevisionRequest $revisionRequest)
    {
        $this->useLanguage('tr');
    }

    protected function messageData(): array
    {
        return ['subject' => $this->mailText('revision_requested').' — '.$this->revisionRequest->work->title, 'heading' => $this->mailText('revision_requested'), 'intro' => $this->mailText('revision_intro', ['name' => $this->revisionRequest->work->customer->title]), 'fields' => [$this->mailText('project') => $this->revisionRequest->work->title], 'notes' => [['label' => $this->mailText('customer_note'), 'text' => $this->revisionRequest->customer_note]], 'actionUrl' => route('admin.works.edit', $this->revisionRequest->work), 'actionLabel' => $this->mailText('admin_work')];
    }
}
