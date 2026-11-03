<?php

namespace App\Http\Controllers\Public;

use App\Mail\RevisionRequestedMail;
use App\Mail\WorkApprovedMail;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class WorkDeliveryController
{
    public function show(Work $work): View
    {
        $work->load(['customer', 'revisionRequests', 'deliveryLinks' => fn ($q) => $q->whereNotNull('released_at')]);

        app()->setLocale($work->customer?->preferred_locale ?: 'tr');

        $openRevision = $work->revisionRequests->first(fn ($r) => in_array($r->status, ['requested', 'in_review']));

        $actionable = $work->delivered_at && ! $openRevision && ! in_array($work->status, ['completed', 'cancelled']);

        return view('public.works.show', [
            'work' => $work,
            'openRevision' => $openRevision,
            'actionable' => $actionable,
            'approveUrl' => $actionable ? URL::temporarySignedRoute('public.works.approve', now()->addMinutes(30), ['work' => $work->id]) : null,
            'revisionUrl' => $actionable ? URL::temporarySignedRoute('public.works.requestRevision', now()->addMinutes(30), ['work' => $work->id]) : null,
        ]);
    }

    public function approve(Work $work): View
    {
        $work = DB::transaction(function () use ($work) {
            $work = Work::with('customer')->whereKey($work->id)->lockForUpdate()->firstOrFail();
            if ($work->status === 'completed') {
                return $work;
            }
            $this->assertActionable($work);
            $work->update(['status' => 'completed']);
            Mail::to(config('charlot.admin_notification_email'))->queue((new WorkApprovedMail($work))->afterCommit());

            return $work;
        });

        if (! request()->routeIs('customer.*')) {
            app()->setLocale($work->customer?->preferred_locale ?: 'tr');
        }

        return view('public.works.result', ['work' => $work, 'action' => 'approved']);
    }

    public function requestRevision(Request $request, Work $work): View
    {
        $data = $request->validate([
            'customer_note' => ['required', 'string', 'max:5000'],
        ]);

        $work = DB::transaction(function () use ($work, $data) {
            $work = Work::with('customer')->whereKey($work->id)->lockForUpdate()->firstOrFail();
            $this->assertActionable($work);
            $revisionRequest = $work->revisionRequests()->create([
                'status' => 'requested',
                'customer_note' => $data['customer_note'],
                'requested_at' => now(),
            ]);
            Mail::to(config('charlot.admin_notification_email'))->queue((new RevisionRequestedMail($revisionRequest))->afterCommit());

            return $work;
        });

        if (! request()->routeIs('customer.*')) {
            app()->setLocale($work->customer?->preferred_locale ?: 'tr');
        }

        return view('public.works.result', ['work' => $work, 'action' => 'revision-requested']);
    }

    private function assertActionable(Work $work): void
    {
        abort_unless($work->delivered_at && ! in_array($work->status, ['completed', 'cancelled']), 409, 'Bu teslimat şu anda yanıtlanamaz.');
        abort_if($work->revisionRequests()->whereIn('status', ['requested', 'in_review'])->exists(), 409, 'Açık revizyon talebiniz zaten bulunuyor.');
    }
}
