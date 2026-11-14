<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispositionRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'disposition_id',
        'recipient_name',
        'recipient_type',
        'primary_bank_account_id',
        'primary_account_name',
        'primary_bank_name',
        'primary_account_number',
        'secondary_bank_account_id',
        'secondary_account_name',
        'secondary_bank_name',
        'secondary_account_number',
        'allocated_amount',
        'notes',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function getAmountAttribute(): float
    {
        return (float) ($this->allocated_amount ?? 0);
    }

    public function getPrimaryAccountHolderAttribute(): ?string
    {
        return $this->primary_account_name;
    }

    public function getSecondaryAccountHolderAttribute(): ?string
    {
        return $this->secondary_account_name;
    }

    public function getRecipientTypeLabelAttribute(): string
    {
        return match ($this->recipient_type) {
            'driver' => 'Sopir / Driver',
            'employee' => 'Karyawan',
            'vendor' => 'Vendor / Rekanan',
            default => 'Lainnya',
        };
    }

    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    public function primaryBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'primary_bank_account_id');
    }

    public function secondaryBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'secondary_bank_account_id');
    }
}
