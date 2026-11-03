<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkStatusEvent extends Model
{
    protected $fillable = ['work_id', 'from_status', 'to_status', 'changed_by', 'note'];

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }
}
