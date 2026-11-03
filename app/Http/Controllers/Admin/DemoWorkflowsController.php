<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class DemoWorkflowsController extends Controller
{
    public function __invoke()
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
        $disk = Storage::disk('local');
        $demos = $disk->exists('demo-workflows.json') ? json_decode($disk->get('demo-workflows.json'), true, 512, JSON_THROW_ON_ERROR) : [];

        return response()->view('admin.demo-workflows', compact('demos'))->header('Cache-Control', 'private, no-store');
    }
}
