<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_POSTED = 'posted';
    public const STATUS_REVERSED = 'reversed';

    public const METHOD_TRANSFER = 'transfer';
    public const METHOD_CASH = 'cash';
    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'disposition_id',
        'payment_number',
        'payment_date',
        'amount',
        'method',
        'source_account_id',
        'recipient_id',
        'reference_number',
        'notes',
        'proof_path',
        'proof_name',
        'recorded_by',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'reversed_at' => 'datetime',
    ];

    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'source_account_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(DispositionRecipient::class, 'recipient_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * Generate nomor transaksi pembayaran unik secara aman.
     * Contoh: BAYAR/2026/11/0001
     */
    public static function generatePaymentNumber(): string
    {
        $year = (int) date('Y');
        $month = date('m');
        
        $count = DB::table('payments')
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->count() + 1;

        $padded = str_pad((string)$count, 4, '0', STR_PAD_LEFT);

        return "PAY/{$year}/{$month}/{$padded}";
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            self::METHOD_TRANSFER => 'Transfer Bank',
            self::METHOD_CASH => 'Tunai (Cash)',
            default => 'Lainnya',
        };
    }

    public function getPaymentMethodAttribute(): string
    {
        return $this->method;
    }

    public function getProofAttachmentAttribute(): ?string
    {
        return $this->proof_path;
    }
}
