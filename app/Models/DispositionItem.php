<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispositionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'disposition_id',
        'category_id',
        'description',
        'qty',
        'unit',
        'amount',
        'receipt_path',
        'receipt_name',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function getItemNameAttribute(): string
    {
        return $this->description ?? '';
    }

    public function getQuantityAttribute(): float
    {
        return (float) ($this->qty ?? 1.0);
    }

    public function getUnitAttribute(): string
    {
        return $this->attributes['unit'] ?? 'Pcs';
    }

    public function getUnitPriceAttribute(): float
    {
        $quantity = (float) ($this->qty ?? 1.0);
        if ($quantity > 0) {
            return round((float) ($this->amount ?? 0) / $quantity, 2);
        }
        return (float) ($this->amount ?? 0);
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path ? asset('storage/' . $this->receipt_path) : null;
    }

    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
