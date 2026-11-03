<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()->when($request->string('q'), fn ($query, $term) => $query->where(fn ($q) => $q->where('title', 'like', '%'.$term.'%')->orWhere('company_name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))->withCount(['quotes', 'works', 'account'])->orderBy('title')->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('admin.customers.form', ['customer' => new Customer]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validated($request));

        return redirect()->route('admin.customers.edit', $customer)->with('success', 'Müşteri kaydedildi.');
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['quotes', 'works', 'account']);

        return view('admin.customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));

        return back()->with('success', 'Müşteri bilgileri güncellendi.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Müşteri arşivlendi.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        $emailRule = 'unique:customers,email'.($customer ? ','.$customer->id.',id' : '');

        return $request->validate([
            'title' => ['required', 'string', 'max:200'], 'company_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255', $emailRule], 'phone' => ['nullable', 'string', 'max:50'],
            'preferred_locale' => ['sometimes', 'required', 'in:tr,en'],
            'country' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:10000'],
        ]);
    }
}
