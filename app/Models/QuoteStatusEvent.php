<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteStatusEvent extends Model
{
    protected $fillable = ['quote_id', 'from_status', 'to_status', 'changed_by', 'note'];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
