<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['ip_address', 'ip_hash', 'visitor_hash'];

    protected function casts(): array
    {
        return ['visited_at' => 'datetime', 'ip_address' => 'encrypted'];
    }
}
