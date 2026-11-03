<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function edit(SiteSettings $settings): View
    {
        return view('admin.site-settings', ['settings' => $settings->values()]);
    }

    public function update(Request $request, SiteSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'homepage_variant' => ['sometimes', 'required', 'in:home-1,home-2,home-3'],
            'name' => ['required', 'string', 'max:200'], 'email' => ['required', 'email:rfc', 'max:255'],
            'telephone' => ['required', 'string', 'max:60', 'regex:~^\\+?[0-9 ()\\-\\.]+$~'],
            'whatsapp_number' => ['nullable', 'string', 'max:60', 'regex:~^\\+?[0-9 ()\\-\\.]+$~'],
            'street_address' => ['nullable', 'string', 'max:255'], 'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'], 'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'country_name' => ['nullable', 'string', 'max:255'], 'address' => ['nullable', 'string', 'max:2000'],
            'map_query' => ['nullable', 'string', 'max:2000'], 'map_url' => ['nullable', 'url:https', 'max:2000'],
            'instagram_url' => ['nullable', 'url:https', 'max:2000'], 'vimeo_url' => ['nullable', 'url:https', 'max:2000'],
            'linkedin_url' => ['nullable', 'url:https', 'max:2000'],
        ]);
        $data['country'] = strtoupper($data['country'] ?? 'TR');
        $model = SiteSetting::firstOrNew(['id' => 1]);
        $model->fill(array_replace($settings->values(), $data))->save();
        Cache::forget('charlot:site-settings');
        Cache::forever('charlot:content-version', bin2hex(random_bytes(8)));

        return back()->with('success', 'Site ayarları kaydedildi.');
    }
}
