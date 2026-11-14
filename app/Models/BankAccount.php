<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    use HasFactory;

    public const TYPE_EMPLOYEE = 'employee';
    public const TYPE_DRIVER = 'driver';
    public const TYPE_VENDOR = 'vendor';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'owner_name',
        'bank_id',
        'bank_name',
        'account_number',
        'owner_type',
        'owner_reference',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'account_number' => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke entitas referensi Bank.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Pengguna yang mendaftarkan rekening ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Label representatif untuk jenis pemilik rekening.
     */
    public function getOwnerTypeLabelAttribute(): string
    {
        return match ($this->owner_type) {
            self::TYPE_EMPLOYEE => 'Karyawan',
            self::TYPE_DRIVER => 'Sopir',
            self::TYPE_VENDOR => 'Vendor',
            default => 'Lainnya',
        };
    }

    /**
     * Scope rekening yang berstatus aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

