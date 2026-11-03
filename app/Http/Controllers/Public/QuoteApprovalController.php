<?php

namespace App\Http\Controllers\Public;

use App\Mail\QuoteAcceptedMail;
use App\Mail\QuoteDeclinedMail;
use App\Models\Quote;
use Dompdf\Dompdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class QuoteApprovalController
{
    public function show(Quote $quote): View
    {
        $quote->load(['customer', 'items']);
        if (! request()->routeIs('customer.*')) {
            app()->setLocale($quote->customer?->preferred_locale ?: 'tr');
        }

        if ($quote->status === 'sent' && $quote->isExpired()) {
            $quote->update(['status' => 'expired']);
        }

        $actionable = $quote->status === 'sent';

        return view('quotes.document', [
            'pdf' => false,
            'logo' => asset('assets/logo.png'),
            'pdfUrl' => URL::temporarySignedRoute('public.quotes.pdf', now()->addMinutes(30), ['quote' => $quote->id]),
            'quote' => $quote,
            'actionable' => $actionable,
            'acceptUrl' => $actionable ? URL::temporarySignedRoute('public.quotes.accept', now()->addMinutes(30), ['quote' => $quote->id]) : null,
            'declineUrl' => $actionable ? URL::temporarySignedRoute('public.quotes.decline', now()->addMinutes(30), ['quote' => $quote->id]) : null,
        ]);
    }

    public function pdf(Quote $quote): Response
    {
        $quote->load(['customer', 'items']);
        if (! request()->routeIs('customer.*')) {
            app()->setLocale($quote->customer?->preferred_locale ?: 'tr');
        }
        $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('assets/logo.png')));
        $pdf = new Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
        $pdf->loadHtml(view('quotes.document', ['quote' => $quote, 'pdf' => true, 'logo' => $logo])->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="charlot-crea-teklif-'.$quote->id.'.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function accept(Quote $quote): View
    {
        return $this->resolve($quote, 'accepted');
    }

    public function decline(Quote $quote): View
    {
        return $this->resolve($quote, 'declined');
    }

    private function resolve(Quote $quote, string $status): View
    {
        $quote = DB::transaction(function () use ($quote, $status) {
            $quote = Quote::with('customer')->whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if ($quote->status === 'sent' && $quote->isExpired()) {
                $quote->update(['status' => 'expired']);
            } elseif ($quote->status === 'sent') {
                $quote->update(['status' => $status]);
                Mail::to(config('charlot.admin_notification_email'))->queue(
                    ($status === 'accepted' ? new QuoteAcceptedMail($quote) : new QuoteDeclinedMail($quote))->afterCommit()
                );
            }

            return $quote;
        });

        if (! request()->routeIs('customer.*')) {
            app()->setLocale($quote->customer?->preferred_locale ?: 'tr');
        }

        return view('public.quotes.result', ['quote' => $quote]);
    }
}
