<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Disposition extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_WAITING_MANAGER = 'waiting_manager';
    public const STATUS_WAITING_FINANCE = 'waiting_finance';
    public const STATUS_WAITING_OWNER = 'waiting_owner';
    public const STATUS_REVISION_REQUESTED = 'revision_requested';
    public const STATUS_REVISION = 'revision_requested';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_APPROVED = 'partially_approved';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIALLY_PAID = 'partially_paid';
    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'disposition_number',
        'invoice_number',
        'disposition_date',
        'subject',
        'applicant_id',
        'division_id',
        'description',
        'approval_status',
        'payment_status',
        'requested_total',
        'ppn',
        'manager_approved_total',
        'finance_verified_total',
        'owner_approved_total',
        'final_approved_total',
        'paid_total',
        'submitted_at',
        'revision_notes',
        'rejected_notes',
    ];

    protected $casts = [
        'disposition_date' => 'date',
        'submitted_at' => 'datetime',
        'requested_total' => 'decimal:2',
        'ppn' => 'decimal:2',
        'manager_approved_total' => 'decimal:2',
        'finance_verified_total' => 'decimal:2',
        'owner_approved_total' => 'decimal:2',
        'final_approved_total' => 'decimal:2',
        'paid_total' => 'decimal:2',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispositionItem::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(DispositionRecipient::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DispositionApproval::class)->orderBy('created_at', 'asc');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function activePayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', 'posted');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Hitung sisa nominal yang belum dibayar.
     */
    public function getRemainingPaymentAttribute(): float
    {
        $final = (float) ($this->final_approved_total ?? $this->requested_total);
        $paid = (float) $this->paid_total;
        return max(0.0, $final - $paid);
    }

    /**
     * Label representatif status persetujuan.
     */
    public function getApprovalStatusLabelAttribute(): string
    {
        return match ($this->approval_status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_WAITING_MANAGER => 'Menunggu Manager',
            self::STATUS_WAITING_FINANCE => 'Menunggu Keputusan Finance (Arahan Owner)',
            self::STATUS_WAITING_OWNER => 'Menunggu Keputusan Finance (Arahan Owner)',
            self::STATUS_REVISION_REQUESTED => 'Perlu Revisi',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_APPROVED => 'Disetujui (ACC Final)',
            default => $this->approval_status,
        };
    }

    /**
     * Badge status persetujuan untuk Blade.
     */
    public function getApprovalStatusBadgeAttribute(): string
    {
        return match ($this->approval_status) {
            self::STATUS_DRAFT => 'bg-slate-100 text-slate-700 border-slate-200',
            self::STATUS_WAITING_MANAGER => 'bg-amber-50 text-amber-700 border-amber-200',
            self::STATUS_WAITING_FINANCE => 'bg-blue-50 text-blue-700 border-blue-200',
            self::STATUS_WAITING_OWNER => 'bg-blue-50 text-blue-700 border-blue-200',
            self::STATUS_REVISION_REQUESTED => 'bg-orange-50 text-orange-700 border-orange-200',
            self::STATUS_REJECTED => 'bg-rose-50 text-rose-700 border-rose-200',
            self::STATUS_APPROVED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }

    /**
     * Label representatif status pembayaran.
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_UNPAID => 'Belum Dibayar',
            self::PAYMENT_PARTIALLY_PAID => 'Dibayar Sebagian',
            self::PAYMENT_PAID => 'Lunas',
            default => $this->payment_status,
        };
    }

    public function getPaymentStatusBadgeAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_UNPAID => 'bg-slate-100 text-slate-700 border-slate-200',
            self::PAYMENT_PARTIALLY_PAID => 'bg-amber-50 text-amber-700 border-amber-200',
            self::PAYMENT_PAID => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }

    /**
     * Cek apakah disposisi dapat diedit oleh user tertentu.
     * Admin/Finance: full access (dapat mengedit data disposisi).
     * Manager: tidak memiliki akses untuk mengedit atau menghapus data disposisi.
     * Pemohon: hanya jika draft atau revisi milik sendiri.
     */
    public function canBeEditedBy(User $user): bool
    {
        if ($user->isAdminFinance()) {
            return true;
        }

        if ($user->isManager()) {
            return false;
        }

        if ($this->applicant_id === $user->id) {
            return in_array($this->approval_status, [self::STATUS_DRAFT, self::STATUS_REVISION_REQUESTED], true);
        }

        return false;
    }

    /**
     * Cek apakah disposisi dapat dihapus oleh user tertentu.
     * Admin/Finance: dapat menghapus data disposisi.
     * Manager: tidak memiliki akses hapus.
     * Pemohon: hanya draft miliknya sendiri.
     */
    public function canBeDeletedBy(User $user): bool
    {
        if ($user->isAdminFinance()) {
            return true;
        }

        if ($user->isManager()) {
            return false;
        }

        if ($this->applicant_id === $user->id) {
            return $this->approval_status === self::STATUS_DRAFT;
        }

        return false;
    }

    /**
     * Cek apakah disposisi siap disetujui/ditinjau oleh user tertentu.
     */
    public function canBeApprovedBy(User $user): bool
    {
        // Tahap Finance/Owner merupakan putusan akhir atas arahan Owner.
        // Jika pemohon adalah Finance (misal Indah Novitasari), Finance tetap berwenang mengeksekusi putusan akhir atas arahan Owner setelah diverifikasi Manager.
        $isFinanceFinalStage = in_array($this->approval_status, [self::STATUS_WAITING_FINANCE, self::STATUS_WAITING_OWNER])
            && ($user->isAdminFinance() || $user->isOwner());

        // Pada tahap Manager, pemohon dilarang menyetujui pengajuannya sendiri
        if ($this->applicant_id === $user->id && !$isFinanceFinalStage) {
            return false;
        }

        if ($this->approval_status === self::STATUS_WAITING_MANAGER && $user->isManager()) {
            return true;
        }

        if ($this->approval_status === self::STATUS_WAITING_FINANCE && ($user->isAdminFinance() || $user->isOwner())) {
            return true;
        }

        if ($this->approval_status === self::STATUS_WAITING_OWNER && $user->isOwner()) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah disposisi dapat dicatat pembayarannya oleh user.
     */
    public function canBePaidBy(User $user): bool
    {
        if ($this->approval_status !== self::STATUS_APPROVED) {
            return false;
        }

        if ($this->remaining_payment <= 0.001) {
            return false;
        }

        return $user->isAdminFinance() || $user->isOwner();
    }

    /**
     * Perbarui status pembayaran berdasarkan total kas yang sudah dibayarkan.
     */
    public function syncPaymentStatus(): void
    {
        $paid = (float) $this->activePayments()->sum('amount');
        $final = (float) ($this->final_approved_total ?? $this->requested_total);

        $this->paid_total = $paid;

        if ($paid <= 0) {
            $this->payment_status = self::PAYMENT_UNPAID;
        } elseif ($paid >= $final - 0.01) {
            $this->payment_status = self::PAYMENT_PAID;
        } else {
            $this->payment_status = self::PAYMENT_PARTIALLY_PAID;
        }

        $this->save();
    }
}
