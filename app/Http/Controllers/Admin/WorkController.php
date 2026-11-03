<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkController extends Controller
{
    public function index(Request $request): View
    {
        $works = Work::with(['customer', 'quote'])->when($request->string('q'), fn ($query, $term) => $query->where('title', 'like', '%'.$term.'%'))->orderByRaw('due_at is null')->orderBy('due_at')->paginate(20)->withQueryString();

        return view('admin.works.index', compact('works'));
    }

    public function create(): View
    {
        return view('admin.works.form', ['work' => new Work, 'customers' => Customer::orderBy('title')->get(), 'quotes' => Quote::with('customer')->latest('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $work = Work::create($this->validated($request));

        return redirect()->route('admin.works.edit', $work)->with('success', 'İş kaydedildi.');
    }

    public function edit(Work $work): View
    {
        $work->load(['customer', 'quote', 'statusEvents', 'revisionRequests', 'files', 'deliveryLinks']);

        return view('admin.works.form', [
            'work' => $work,
            'customers' => Customer::orderBy('title')->get(),
            'quotes' => Quote::with('customer')->latest('id')->get(),
            'customerLink' => $work->delivered_at ? $work->signedShowUrl() : null,
        ]);
    }

    public function update(Request $request, Work $work): RedirectResponse
    {
        $work->update($this->validated($request));

        return back()->with('success', 'İş güncellendi.');
    }

    public function destroy(Work $work): RedirectResponse
    {
        $work->delete();

        return redirect()->route('admin.works.index')->with('success', 'İş arşivlendi.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'], 'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'], 'status' => ['required', 'in:'.implode(',', Work::STATUSES)],
            'starts_at' => ['nullable', 'date'], 'due_at' => ['nullable', 'date'], 'brief' => ['nullable', 'string', 'max:20000'],
            'internal_notes' => ['nullable', 'string', 'max:20000'],
        ]);
    }
}
