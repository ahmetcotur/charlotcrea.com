<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        if (Auth::guard('customers')->user()?->canAccess() && Auth::guard('customers')->user()?->activated_at) {
            return redirect()->route('customer.dashboard');
        }

        return view('customer.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string', 'max:255']]);
        $data['email'] = Str::lower(trim($data['email']));
        if (! Auth::guard('customers')->attempt([...$data, 'enabled' => true], false)) {
            throw ValidationException::withMessages(['email' => __('The email or password is incorrect.')]);
        }
        $account = Auth::guard('customers')->user();
        if (! $account->canAccess() || ! $account->activated_at) {
            Auth::guard('customers')->logout();
            throw ValidationException::withMessages(['email' => __('The email or password is incorrect.')]);
        }
        $request->session()->regenerate();
        $request->session()->put('customer_auth_version', hash('sha256', $account->password));

        return redirect()->route('customer.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('customers')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }

    public function forgotForm()
    {
        return view('customer.forgot-password');
    }

    public function forgot(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower(trim($data['email']));
        $account = CustomerAccount::where('email', $email)->first();
        if ($account?->canAccess()) {
            Password::broker('customers')->sendResetLink(['email' => $email, 'enabled' => true]);
        }

        return back()->with('status', __('If an account exists, a password link has been queued for email.'));
    }

    public function resetForm(Request $request, string $token)
    {
        return view('customer.reset-password', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', 'max:255', PasswordRule::min(12)]]);
        $data['email'] = Str::lower(trim($data['email']));
        $status = Password::broker('customers')->reset($data, function (CustomerAccount $account, string $password) {
            if (! $account->canAccess()) {
                throw ValidationException::withMessages(['email' => __('This account is not available.')]);
            }
            $account->forceFill(['password' => $password, 'activated_at' => now(), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($account));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __('The password link is invalid or expired. Request a new link.')]);
        }
        Auth::guard('customers')->logout();
        $request->session()->regenerate();

        return redirect()->route('customer.login')->with('status', __('Your password is ready. You can now sign in.'));
    }
}
