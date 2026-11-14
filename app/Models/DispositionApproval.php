<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispositionApproval extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const STAGE_MANAGER = 'manager';
    public const STAGE_FINANCE = 'finance';
    public const STAGE_OWNER = 'owner';

    public const DECISION_APPROVED = 'approved';
    public const DECISION_PARTIALLY_APPROVED = 'partially_approved';
    public const DECISION_REVISION_REQUESTED = 'revision_requested';
    public const DECISION_REJECTED = 'rejected';

    protected $fillable = [
        'disposition_id',
        'stage',
        'actor_id',
        'decision',
        'previous_amount',
        'approved_amount',
        'notes',
        'revision_round',
        'created_at',
    ];

    protected $casts = [
        'previous_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function disposition(): BelongsTo
    {
        return $this->belongsTo(Disposition::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function getDecisionLabelAttribute(): string
    {
        return match ($this->decision) {
            self::DECISION_APPROVED => 'Disetujui Penuh',
            self::DECISION_PARTIALLY_APPROVED => 'Disetujui Sebagian',
            self::DECISION_REVISION_REQUESTED => 'Diminta Revisi',
            self::DECISION_REJECTED => 'Ditolak',
            default => $this->decision,
        };
    }

    public function getStepAttribute(): string
    {
        return $this->stage;
    }

    public function getActionAttribute(): string
    {
        return $this->decision;
    }
}
