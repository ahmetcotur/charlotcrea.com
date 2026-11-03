<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\QuoteApprovalController;
use App\Http\Controllers\Public\WorkDeliveryController;
use App\Models\Quote;
use App\Models\Work;
use App\Models\WorkFile;
use App\Services\DeliveryDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $customer = $request->user('customers')->customer;
        $quotes = $customer->quotes()->where('published', true)->where('status', '!=', 'draft')->latest('id')->get();
        $works = $customer->works()->where('published', true)->withCount(['deliveryLinks' => fn ($q) => $q->whereNotNull('released_at')])->latest('id')->get();

        return view('customer.dashboard', compact('customer', 'quotes', 'works'));
    }

    private function quote(Request $request, int $id): Quote
    {
        $quote = $request->user('customers')->customer->quotes()->where('published', true)->where('status', '!=', 'draft')->findOrFail($id);
        Gate::forUser($request->user('customers'))->authorize('view', $quote);

        return $quote;
    }

    private function work(Request $request, int $id): Work
    {
        $work = $request->user('customers')->customer->works()->where('published', true)->findOrFail($id);
        Gate::forUser($request->user('customers'))->authorize('view', $work);

        return $work;
    }

    public function showQuote(Request $request, int $id)
    {
        $quote = $this->quote($request, $id)->load('items');
        if ($quote->status === 'sent' && $quote->isExpired()) {
            $quote->update(['status' => 'expired']);
        }

        return view('customer.quote', compact('quote'));
    }

    public function quotePdf(Request $request, int $id)
    {
        return app(QuoteApprovalController::class)->pdf($this->quote($request, $id));
    }

    public function acceptQuote(Request $request, int $id)
    {
        app(QuoteApprovalController::class)->accept($this->quote($request, $id));

        return redirect()->route('customer.quotes.show', $id)->with('status', __('Your response has been saved.'));
    }

    public function declineQuote(Request $request, int $id)
    {
        app(QuoteApprovalController::class)->decline($this->quote($request, $id));

        return redirect()->route('customer.quotes.show', $id)->with('status', __('Your response has been saved.'));
    }

    public function showWork(Request $request, int $id)
    {
        $work = $this->work($request, $id)->load(['deliveryLinks' => fn ($q) => $q->whereNotNull('released_at'), 'revisionRequests']);
        $openRevision = $work->revisionRequests->first(fn ($r) => in_array($r->status, ['requested', 'in_review']));

        return view('customer.work', compact('work', 'openRevision'));
    }

    public function approveWork(Request $request, int $id)
    {
        app(WorkDeliveryController::class)->approve($this->work($request, $id));

        return redirect()->route('customer.works.show', $id)->with('status', __('Your response has been saved.'));
    }

    public function revision(Request $request, int $id)
    {
        app(WorkDeliveryController::class)->requestRevision($request, $this->work($request, $id));

        return redirect()->route('customer.works.show', $id)->with('status', __('Your revision request has been received.'));
    }

    public function download(Request $request, int $id, WorkFile $file, DeliveryDownload $download)
    {
        $work = $this->work($request, $id);
        $download->assertReleased($work, $file);

        return $download->stream($file);
    }
}
