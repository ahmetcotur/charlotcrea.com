<?php

namespace App\Http\Middleware;

use A17\Twill\Http\Middleware\Authenticate as TwillAuthenticate;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

/**
 * Twill's own Authenticate middleware hardcodes its guest/unpublished redirect
 * to Twill's own login route (config('twill.admin_route_name_prefix').'login.form'),
 * which now points at the vestigial /legacy-cms panel. This override sends guests
 * to this app's real admin.login route instead.
 */
class AdminAuthenticate extends TwillAuthenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        if ((! $request->user() || ! $request->user()->published) && $request->route()?->getName() !== 'admin.login') {
            Auth::logout();

            return $request->expectsJson()
                ? abort(403, 'Your account is not verified.')
                : Redirect::guest(URL::route('admin.login'));
        }

        return $next($request);
    }

    protected function redirectTo($request)
    {
        return route('admin.login');
    }
}
