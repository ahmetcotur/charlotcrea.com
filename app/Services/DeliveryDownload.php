<?php

namespace App\Services;

use App\Models\Work;
use App\Models\WorkFile;
use Illuminate\Support\Facades\Storage;

class DeliveryDownload
{
    public function assertReleased(Work $work, WorkFile $file): void
    {
        abort_unless($file->work_id === $work->id && $file->released_at && $work->delivered_at && $work->published && $work->status !== 'cancelled', 404);
    }

    public function stream(WorkFile $file)
    {
        abort_unless(Storage::disk('deliveries')->exists($file->path), 404);

        return Storage::disk('deliveries')->download($file->path, $file->name, [
            'Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0', 'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
