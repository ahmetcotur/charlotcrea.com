<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\WorkDeliveredMail;
use App\Models\Work;
use App\Observers\Concerns\ResolvesChangedBy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WorkDeliverController extends Controller
{
    use ResolvesChangedBy;

    public function __invoke(Request $request, Work $work): RedirectResponse
    {
        $data = $request->validate([
            'delivery_note' => ['required', 'string', 'max:20000'],
        ]);

        DB::transaction(function () use ($work, $data) {
            $work = Work::whereKey($work->id)->lockForUpdate()->firstOrFail();
            abort_if($work->status === 'cancelled', 409, 'İptal edilmiş iş teslim edilemez.');
            $work->deliveryLinks()->whereNull('released_at')->update(['released_at' => now()]);
            $work->update([
                'delivered_at' => now(),
                'status' => $work->status === 'completed' ? 'in_progress' : $work->status,
                'delivery_note' => $data['delivery_note'],
            ]);

            $work->statusEvents()->create([
                'from_status' => $work->status,
                'to_status' => $work->status,
                'changed_by' => $this->resolveChangedBy(),
                'note' => 'Teslim edildi: '.$data['delivery_note'],
            ]);

        });
        $work->refresh();

        if (blank($work->customer->email)) {
            return back()->with('success', 'Teslimat kaydedildi. Müşterinin e-postası tanımlı değil — bağlantıyı aşağıdan kopyalayıp WhatsApp ile iletebilirsiniz.');
        }

        Mail::to($work->customer->email)->queue(new WorkDeliveredMail($work, $work->signedShowUrl()));

        return back()->with('success', 'Teslimat kaydedildi; müşteri bildirimi e-posta kuyruğuna alındı.');
    }
}
