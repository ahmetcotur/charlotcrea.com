<?php

namespace App\Observers;

use App\Models\Work;
use App\Observers\Concerns\ResolvesChangedBy;

class WorkObserver
{
    use ResolvesChangedBy;

    public function created(Work $work): void
    {
        $this->record($work, null, $work->status);
    }

    public function updated(Work $work): void
    {
        if ($work->wasChanged('status')) {
            $this->record($work, $work->getOriginal('status'), $work->status);
        }
    }

    private function record(Work $work, ?string $from, string $to, ?string $note = null): void
    {
        $work->statusEvents()->create([
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $this->resolveChangedBy(),
            'note' => $note ?? $this->customerActionNote(),
        ]);
    }
}
