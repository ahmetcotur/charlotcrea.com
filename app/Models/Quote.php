<?php

namespace App\Models;

use A17\Twill\Models\Behaviors\HasRevisions;
use A17\Twill\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

class Quote extends Model
{
    use HasRevisions;

    public const STATUSES = ['draft', 'sent', 'accepted', 'declined', 'expired'];

    protected $attributes = [
        'status' => 'draft',
        'amount' => 0,
        'currency' => 'EUR',
        'published' => false,
    ];

    protected $fillable = [
        'published',
        'title',
        'customer_id',
        'quote_number',
        'status',
        'amount',
        'currency',
        'valid_until',
        'scope',
        'internal_notes',
        'sent_at',
        'accepted_at',
        'demo_key',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote): void {
            if (blank($quote->quote_number)) {
                do {
                    $number = 'CC-'.now()->format('Y').'-'.random_int(100000, 999999);
                } while (static::where('quote_number', $number)->exists());

                $quote->quote_number = $number;
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(QuoteStatusEvent::class)->latest('id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->copy()->endOfDay()->isPast();
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    /**
     * A signed, no-login link to this quote's customer-facing page. Expiry
     * follows the quote's own validity window (or 14 days if none is set).
     */
    public function signedShowUrl(): string
    {
        $expiry = $this->valid_until ? $this->valid_until->copy()->endOfDay() : now()->addDays(14);

        return URL::temporarySignedRoute('public.quotes.show', $expiry, ['quote' => $this->id]);
    }

    /**
     * Replace all line items with the given rows and recompute `amount`
     * as their sum. Called from the admin controller on store/update.
     *
     * @param  array<int, array{title: string, description?: ?string, quantity: float, unit_price: float}>  $rows
     */
    public function syncItems(array $rows): void
    {
        $this->items()->delete();

        $total = 0;
        foreach (array_values($rows) as $index => $row) {
            $quantity = (float) $row['quantity'];
            $unitPrice = (float) $row['unit_price'];
            $total += $quantity * $unitPrice;

            $this->items()->create([
                'title' => $row['title'],
                'description' => $row['description'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'sort_order' => $index,
            ]);
        }

        $this->update(['amount' => $total]);
    }
}
