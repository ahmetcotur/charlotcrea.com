<?php

namespace App\Http\Middleware;

use App\Models\CustomerAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $sessionAccount = Auth::guard('customers')->user();
        $account = $sessionAccount ? CustomerAccount::find($sessionAccount->getAuthIdentifier()) : null;
        if ($account) {
            Auth::guard('customers')->setUser($account);
        }
        if (! $account || ! $account->canAccess() || ! $account->activated_at || $request->session()->get('customer_auth_version') !== hash('sha256', $account->password)) {
            Auth::guard('customers')->logout();
            if ($request->expectsJson()) {
                abort(401);
            }

            return redirect()->guest(route('customer.login'));
        }

        return $next($request);
    }
}
