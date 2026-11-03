<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Work;
use Illuminate\Http\JsonResponse;

class WorkLinkController extends Controller
{
    public function __invoke(Work $work): JsonResponse
    {
        return response()->json(['url' => $work->signedShowUrl()]);
    }
}
