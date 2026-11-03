<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User as ApplicationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::guard('twill_users')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'E-posta veya parola hatalı.'])->onlyInput('email');
        }

        $admin = Auth::guard('twill_users')->user();
        ApplicationUser::firstOrCreate(
            ['email' => $admin->email],
            ['name' => $admin->name, 'password' => Str::random(64)],
        );

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('twill_users')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
