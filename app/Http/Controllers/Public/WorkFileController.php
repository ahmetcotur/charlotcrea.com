<?php

namespace App\Http\Controllers\Public;

use App\Models\Work;
use App\Models\WorkFile;
use App\Services\DeliveryDownload;
use Illuminate\Support\Facades\URL;

class WorkFileController
{
    public function __invoke(Work $work, WorkFile $file, DeliveryDownload $download)
    {
        abort_unless((string) request('customer') === (string) $work->customer_id, 404);
        $download->assertReleased($work, $file);
        if ($work->customer->account) {
            return redirect(URL::temporarySignedRoute('customer.files.download', now()->addMinutes(15), ['id' => $work->id, 'file' => $file->id]));
        }

        return $download->stream($file);
    }
}
