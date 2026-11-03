<?php

namespace App\Observers\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait ResolvesChangedBy
{
    protected function resolveChangedBy(): ?int
    {
        if (request()->routeIs('customer.*')) return null;
        return User::where('email', Auth::guard('twill_users')->user()?->email)->value('id');
    }

    protected function customerActionNote(): ?string
    {
        if (request()->routeIs('customer.*')) return 'Müşteri işlemi (müşteri paneli üzerinden)';
        return Auth::guard('twill_users')->check() ? null : 'Müşteri işlemi (imzalı bağlantı üzerinden)';
    }
}
