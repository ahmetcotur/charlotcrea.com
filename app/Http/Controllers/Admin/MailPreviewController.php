<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MailPreviews;
use Illuminate\Http\Request;

class MailPreviewController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['type' => ['nullable', 'in:'.implode(',', array_keys(MailPreviews::TYPES))], 'lang' => ['nullable', 'in:tr,en']]);

        return response()->view('admin.mail-previews', ['types' => MailPreviews::TYPES, 'type' => $data['type'] ?? 'quote-ready', 'language' => $data['lang'] ?? 'tr'])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function show(Request $request, string $type, MailPreviews $previews)
    {
        $data = $request->validate(['lang' => ['nullable', 'in:tr,en']]);

        return response($previews->make($type, $data['lang'] ?? 'tr')->render())->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
