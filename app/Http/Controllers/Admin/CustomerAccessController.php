<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerAccessController extends Controller
{
    public function invite(Customer $customer)
    {
        if (! $customer->email || ! $customer->published) {
            throw ValidationException::withMessages(['email' => 'Panel erişimi için müşterinin geçerli e-postası ve aktif kaydı olmalı.']);
        }
        $email = Str::lower(trim($customer->email));
        if (CustomerAccount::where('email', $email)->where('customer_id', '!=', $customer->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'Bu e-posta başka bir müşteri hesabına bağlı.']);
        }
        $url = DB::transaction(function () use ($customer, $email) {
            $account = $customer->account()->firstOrNew();
            if (! $account->exists || $account->email !== $email || ! $account->enabled) {
                if ($account->exists) {
                    DB::table('customer_password_reset_tokens')->where('email', $account->email)->delete();
                }
                $account->fill(['email' => $email, 'password' => Str::random(64), 'activated_at' => null]);
                $account->remember_token = null;
            }
            $account->enabled = true;
            $account->save();
            $token = Password::broker('customers')->createToken($account);

            return route('customer.password.reset', ['token' => $token, 'email' => $email]);
        });

        return back()->with('success', 'Panel erişimi hazır. Tek kullanımlık bağlantı 60 dakika geçerlidir; önceki davet bağlantısı geçersizleşti.')->with('customer_invite_url', $url);
    }

    public function revoke(Customer $customer)
    {
        $customer->account()->update(['enabled' => false, 'remember_token' => null]);
        if ($email = $customer->account?->email) {
            DB::table('customer_password_reset_tokens')->where('email', $email)->delete();
        }

        return back()->with('success', 'Müşteri paneli erişimi kapatıldı.');
    }
}
