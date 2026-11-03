<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\RevisionRequest;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['all', 'upcoming', 'approved'])],
            'date_field' => ['nullable', Rule::in(['starts_at', 'due_at', 'created_at'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'year' => ['nullable', 'integer', 'between:1900,2200'],
        ]);
        $filters = array_merge(['view' => 'all', 'date_field' => 'starts_at', 'from' => null, 'to' => null, 'year' => null], array_filter($filters, fn ($value) => $value !== null));
        $works = Work::with(['customer', 'quote']);
        $quotes = Quote::with('customer');
        if ($filters['view'] === 'upcoming') {
            $works->whereDate('starts_at', '>=', today())->whereIn('status', ['planned', 'in_progress', 'on_hold']);
        } elseif ($filters['view'] === 'approved') {
            $works->whereHas('quote', fn ($query) => $query->where('status', 'accepted'))->where('status', '!=', 'cancelled');
            $quotes->where('status', 'accepted');
        }
        // Quote dates represent approval for the approved view, otherwise sending/creation.
        $quoteDate = $filters['view'] === 'approved' ? 'COALESCE(accepted_at, created_at)' : 'COALESCE(sent_at, created_at)';
        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if ($filters[$key]) {
                $works->whereDate($filters['date_field'], $operator, $filters[$key]);
                $quotes->whereDate(DB::raw($quoteDate), $operator, $filters[$key]);
            }
        }
        if ($filters['year']) {
            $works->whereYear($filters['date_field'], $filters['year']);
            $quotes->whereYear(DB::raw($quoteDate), $filters['year']);
        }
        $worksByStatus = $works->orderByRaw($filters['date_field'].' is null')->orderBy($filters['date_field'])->orderBy('id')->get()->groupBy('status');
        $quotesByStatus = $filters['view'] === 'upcoming' ? collect() : $quotes->orderByDesc('id')->get()->groupBy('status');
        $years = collect([now()->year, now()->year + 1]);
        foreach ([Work::query()->get(['starts_at', 'due_at', 'created_at']), Quote::query()->get(['created_at', 'sent_at', 'accepted_at'])] as $records) {
            foreach ($records as $record) {
                foreach ($record->getAttributes() as $field => $value) {
                    if ($value && $record->$field instanceof \DateTimeInterface) {
                        $years->push((int) $record->$field->format('Y'));
                    }
                }
            }
        }
        if ($filters['year']) {
            $years->push((int) $filters['year']);
        }

        return view('admin.crm.index', [
            'filters' => $filters,
            'years' => $years->unique()->sortDesc()->values(),
            'quotesByStatus' => $quotesByStatus,
            'worksByStatus' => $worksByStatus,
            'customers' => Customer::withCount(['quotes', 'works'])->orderByDesc('id')->take(8)->get(),
            'revisionRequests' => RevisionRequest::with('work.customer')->whereIn('status', ['requested', 'in_review'])->orderBy('requested_at')->take(8)->get(),
        ]);
    }
}
