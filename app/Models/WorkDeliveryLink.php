<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkDeliveryLink extends Model
{
    protected $fillable = ['work_id', 'title', 'url', 'released_at'];

    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }
}
