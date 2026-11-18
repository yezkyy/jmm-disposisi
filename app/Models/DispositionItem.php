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
        'invoice_number',
        'qty',
        'unit',
        'amount',
        'receipt_path',
        'receipt_name',
        'notes',
    ];

    protected $casts = [
        'qty' => 'float',
        'amount' => 'decimal:2',
    ];

    public function getQtyAttribute($value): ?float
    {
        if (is_null($value) || trim((string)$value) === '') {
            return null;
        }

        return (float) $value;
    }

    public function getItemNameAttribute(): string
    {
        return $this->description ?? '';
    }

    public function getQuantityAttribute(): ?float
    {
        return $this->qty;
    }

    public function getFormattedQtyAttribute(): ?string
    {
        if (is_null($this->qty)) {
            return null;
        }

        $val = (float) $this->qty;
        if (floor($val) == $val) {
            return (string) (int) $val;
        }

        return rtrim(rtrim(number_format($val, 2, ',', '.'), '0'), ',');
    }

    public function getUnitAttribute(): ?string
    {
        return $this->attributes['unit'] ?? null;
    }

    public function getUnitPriceAttribute(): float
    {
        $quantity = (float) ($this->qty ?? 0);
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
