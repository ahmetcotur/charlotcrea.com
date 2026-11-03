<?php

namespace App\Http\Controllers\Site;

use App\Models\Customer;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreInquiryController
{
    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->filled('website')) {
            return $this->success($request);
        }

        $isQuote = $request->routeIs('quote-requests.store');
        $projectRule = $isQuote ? 'required' : 'nullable';
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255'],
            'property' => [$projectRule, 'string', 'max:200'],
            'country' => [$projectRule, 'string', 'max:120'],
            'city' => [$projectRule, 'string', 'max:120'],
            'service' => [$projectRule, 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:60'],
            'project_type' => [$projectRule, 'string', 'max:120'],
            'budget' => ['nullable', 'string', 'max:120'],
            'usage' => [$projectRule, 'string', 'max:1000'],
            'deliverables' => [$projectRule, 'string', 'max:5000'],
            'timing' => ['nullable', 'string', 'max:160'],
            'details' => ['required', 'string', 'max:20000'],
            'locale' => ['required', 'in:en,tr'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        $data['type'] = $isQuote ? 'quote' : 'contact';
        DB::transaction(function () use ($data): void {
            $customer = Customer::firstOrCreate(
                ['email' => mb_strtolower(trim($data['email']))],
                [
                    'title' => $data['name'],
                    'company_name' => $data['property'] ?? null,
                    'country' => $data['country'] ?? null,
                    'phone' => $data['phone'] ?? null,
                ]
            );

            Inquiry::create([
                ...collect($data)->except(['consent', 'website'])->all(),
                'customer_id' => $customer->id,
                'status' => 'new',
                'received_at' => now(),
            ]);
        });

        return $this->success($request);
    }

    private function success(Request $request): JsonResponse|RedirectResponse
    {
        $message = $request->input('locale') === 'tr'
            ? 'Talebiniz alındı. En kısa sürede sizinle iletişime geçeceğiz.'
            : 'Your enquiry has been received. We will be in touch soon.';

        return $request->expectsJson()
            ? response()->json(['message' => $message], 201)
            : back()->with('inquiry-success', $message);
    }
}
