<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Work;
use App\Models\WorkFile;
use App\Services\DeliveryDownload;
use Illuminate\Support\Facades\Storage;

class WorkFileController extends Controller
{
    public function download(Work $work, WorkFile $file)
    {
        abort_unless($file->work_id === $work->id, 404);

        return app(DeliveryDownload::class)->stream($file);
    }

    public function destroy(Work $work, WorkFile $file)
    {
        abort_unless($file->work_id === $work->id, 404);
        $path = $file->path;
        $file->delete();
        Storage::disk('deliveries')->delete($path);

        return back()->with('success', 'Teslimat dosyası kaldırıldı.');
    }
}
