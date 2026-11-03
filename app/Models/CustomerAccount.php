<?php

namespace App\Models;

use App\Mail\CustomerPasswordMail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

class CustomerAccount extends Authenticatable
{
    protected $fillable = ['customer_id', 'email', 'password', 'enabled', 'activated_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'enabled' => 'boolean', 'activated_at' => 'datetime'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function canAccess(): bool
    {
        return $this->enabled && $this->customer && $this->customer->published;
    }

    public function sendPasswordResetNotification($token): void
    {
        if (! $this->canAccess()) {
            return;
        }
        $url = route('customer.password.reset', ['token' => $token, 'email' => $this->email, 'lang' => app()->getLocale()]);
        Mail::to($this->email)->queue((new CustomerPasswordMail($url, app()->getLocale()))->afterCommit());
    }
}
