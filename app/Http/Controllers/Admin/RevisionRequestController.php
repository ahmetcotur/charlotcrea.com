<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RevisionResolvedMail;
use App\Models\RevisionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class RevisionRequestController extends Controller
{
    public function index(): View
    {
        $revisionRequests = RevisionRequest::with(['work.customer'])
            ->whereIn('status', ['requested', 'in_review'])
            ->orderBy('requested_at')
            ->get();

        return view('admin.revision-requests.index', compact('revisionRequests'));
    }

    public function update(Request $request, RevisionRequest $revisionRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_response' => ['required', 'string', 'max:20000'],
        ]);

        $revisionRequest->load('work.customer');

        $revisionRequest->update([
            'status' => $data['status'],
            'admin_response' => $data['admin_response'],
            'resolved_at' => now(),
        ]);

        if (filled($revisionRequest->work->customer->email)) {
            $signedUrl = URL::temporarySignedRoute('public.works.show', now()->addDays(30), ['work' => $revisionRequest->work_id]);

            Mail::to($revisionRequest->work->customer->email)->queue(new RevisionResolvedMail($revisionRequest, $signedUrl));
        }

        return back()->with('success', 'Revizyon talebi güncellendi.');
    }
}
