<?php

namespace App\Mail;

use App\Models\Work;

class WorkApprovedMail extends BrandedMail
{
    public function __construct(public Work $work)
    {
        $this->useLanguage('tr');
    }

    protected function messageData(): array
    {
        return ['subject' => $this->mailText('work_approved').' — '.$this->work->title, 'heading' => $this->mailText('work_approved'), 'intro' => $this->mailText('approved_intro', ['name' => $this->work->customer->title]), 'fields' => [$this->mailText('project') => $this->work->title], 'actionUrl' => route('admin.works.edit', $this->work), 'actionLabel' => $this->mailText('admin_work')];
    }
}
