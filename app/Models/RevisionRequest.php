<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionRequest extends Model
{
    public const STATUSES = ['requested', 'in_review', 'approved', 'rejected'];

    protected $fillable = ['work_id', 'status', 'customer_note', 'admin_response', 'requested_at', 'resolved_at'];

    protected $attributes = ['status' => 'requested'];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }
}
