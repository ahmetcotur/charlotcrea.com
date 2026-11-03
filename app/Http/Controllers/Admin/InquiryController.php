<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $inquiries = Inquiry::with('customer')->when(in_array($request->input('type'), ['contact','quote']), fn ($query) => $query->where('type', $request->input('type')))->when($request->string('q'), fn ($query, $term) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')->orWhere('property', 'like', '%'.$term.'%')))->orderByDesc('received_at')->paginate(20)->withQueryString();

        return view('admin.inquiries.index', compact('inquiries'));
    }

    public function edit(Inquiry $inquiry): View
    {
        return view('admin.inquiries.edit', compact('inquiry'));
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $inquiry->update($request->validate([
            'status' => ['required', 'in:'.implode(',', Inquiry::STATUSES)],
            'internal_notes' => ['nullable', 'string', 'max:20000'],
        ]));

        return back()->with('success', 'Talep güncellendi.');
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')->with('success', 'Talep arşivlendi.');
    }
}
