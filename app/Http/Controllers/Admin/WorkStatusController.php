<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Work;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkStatusController extends Controller
{
    public function __invoke(Request $request, Work $work): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Work::STATUSES)],
        ]);

        $work->update(['status' => $data['status']]);

        return response()->json(['status' => $work->status]);
    }
}
