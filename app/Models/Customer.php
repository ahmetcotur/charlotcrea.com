<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, HasRevisions;

    protected $fillable = [
        'published',
        'preferred_locale',
        'title',
        'company_name',
        'email',
        'phone',
        'country',
        'notes',
        'demo_key',
    ];

    protected $attributes = ['published' => true, 'preferred_locale' => 'tr'];

    public function account()
    {
        return $this->hasOne(CustomerAccount::class);
    }

    protected static function booted(): void
    {
        static::updated(function (Customer $customer) {
            if ($customer->wasChanged('email')) {
                $customer->account()->update(['enabled' => false, 'remember_token' => null]);
            }
        });
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }
}
