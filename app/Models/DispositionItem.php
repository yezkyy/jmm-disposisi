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
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function getItemNameAttribute(): string
    {
        return $this->description ?? '';
    }

    public function getQuantityAttribute(): float
    {
        return 1.0;
    }

    public function getUnitAttribute(): string
    {
        return 'Unit';
    }

    public function getUnitPriceAttribute(): float
    {
        return (float) ($this->amount ?? 0);
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
