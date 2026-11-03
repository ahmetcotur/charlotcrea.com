<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteStatusController extends Controller
{
    public function __invoke(Request $request, Quote $quote): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Quote::STATUSES)],
        ]);

        $quote = DB::transaction(function () use ($quote, $data) {
            $quote = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            $quote->update(['status' => $data['status']]);

            return $quote;
        });

        return response()->json(['status' => $quote->status]);
    }
}
