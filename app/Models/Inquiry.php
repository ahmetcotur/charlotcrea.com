<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    use HasRevisions;

    public const STATUSES = ['new', 'reviewing', 'replied', 'converted', 'archived'];

    protected $fillable = [
        'published', 'type', 'phone', 'project_type', 'budget', 'usage', 'deliverables',
        'name', 'email', 'property', 'country', 'city', 'service', 'timing',
        'details', 'locale', 'status', 'customer_id', 'internal_notes', 'received_at',
    ];

    protected $attributes = ['status' => 'new', 'published' => true];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }

    public function getTitleAttribute(): string
    {
        return $this->name.($this->property ? ' — '.$this->property : '');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
