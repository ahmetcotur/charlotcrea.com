<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function index(Request $request): View
    {
        $quotes = Quote::with('customer')->when($request->string('q'), fn ($query, $term) => $query->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('quote_number', 'like', '%'.$term.'%')))->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.quotes.index', compact('quotes'));
    }

    public function create(): View
    {
        return view('admin.quotes.form', [
            'quote' => new Quote,
            'customers' => Customer::orderBy('title')->get(),
            'defaultItems' => [['title' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0]],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $quote = DB::transaction(function () use ($data) {
            $quote = Quote::create([...collect($data)->except('items')->all(), 'amount' => 0]);
            $quote->syncItems($data['items']);

            return $quote;
        });

        return redirect()->route('admin.quotes.edit', $quote)->with('success', 'Teklif oluşturuldu.');
    }

    public function edit(Quote $quote): View
    {
        $quote->load(['customer', 'statusEvents', 'items', 'works']);

        $canSend = ! $quote->isExpired();

        $defaultItems = $quote->items->isNotEmpty()
            ? $quote->items->map(fn ($item) => ['title' => $item->title, 'description' => $item->description, 'quantity' => (float) $item->quantity, 'unit_price' => (float) $item->unit_price])->all()
            : [['title' => $quote->exists ? 'Teklif tutarı' : '', 'description' => '', 'quantity' => 1, 'unit_price' => $quote->exists ? (float) $quote->amount : 0]];

        return view('admin.quotes.form', [
            'quote' => $quote,
            'customers' => Customer::orderBy('title')->get(),
            'canSend' => $canSend,
            'defaultItems' => $defaultItems,
            'customerLink' => $quote->exists ? $quote->signedShowUrl() : null,
        ]);
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        $data = $this->validated($request, $quote);
        DB::transaction(function () use ($quote, $data) {
            $quote = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            $quote->update(collect($data)->except('items')->all());
            $quote->syncItems($data['items']);
        });

        return back()->with('success', 'Teklif güncellendi.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return redirect()->route('admin.quotes.index')->with('success', 'Teklif arşivlendi.');
    }

    private function validated(Request $request, ?Quote $quote = null): array
    {
        $numberRule = 'unique:quotes,quote_number'.($quote ? ','.$quote->id.',id' : '');

        return $request->validate([
            'title' => ['required', 'string', 'max:200'], 'quote_number' => ['nullable', 'string', 'max:40', $numberRule],
            'customer_id' => ['required', 'integer', 'exists:customers,id'], 'status' => ['required', 'in:'.implode(',', Quote::STATUSES)],
            'currency' => ['required', 'in:EUR,USD,TRY'],
            'valid_until' => ['nullable', 'date'], 'scope' => ['nullable', 'string', 'max:20000'], 'internal_notes' => ['nullable', 'string', 'max:20000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:200'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);
    }
}
