<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;

class QuoteLinkController extends Controller
{
    public function __invoke(Quote $quote): JsonResponse
    {
        return response()->json(['url' => $quote->signedShowUrl()]);
    }
}
