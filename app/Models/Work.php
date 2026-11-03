<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

class Work extends Model
{
    use HasRevisions;

    public const STATUSES = ['planned', 'in_progress', 'on_hold', 'completed', 'cancelled'];

    protected $fillable = [
        'published',
        'title',
        'customer_id',
        'quote_id',
        'status',
        'starts_at',
        'due_at',
        'brief',
        'internal_notes',
        'demo_key',
        'delivered_at',
        'delivery_note',
    ];

    protected $attributes = ['status' => 'planned', 'published' => true];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'due_at' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    public function deliveryLinks(): HasMany
    {
        return $this->hasMany(WorkDeliveryLink::class)->orderBy('id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(WorkFile::class)->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(WorkStatusEvent::class)->latest('id');
    }

    public function revisionRequests(): HasMany
    {
        return $this->hasMany(RevisionRequest::class)->latest('requested_at');
    }

    /** A signed, no-login link to this work's customer-facing delivery page. */
    public function signedShowUrl(): string
    {
        return URL::temporarySignedRoute('public.works.show', now()->addDays(30), ['work' => $this->id]);
    }
}
