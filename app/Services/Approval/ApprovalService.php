<?php

namespace App\Services\Approval;

use App\Models\AuditLog;
use App\Models\Disposition;
use App\Models\DispositionApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApprovalService
{
    /**
     * Proses putusan persetujuan (approval) berjenjang.
     *
     * @throws InvalidArgumentException
     */
    public function processApproval(
        User $actor,
        Disposition $disposition,
        string $decision,
        ?float $approvedAmount = null,
        ?string $notes = null
    ): DispositionApproval {
        // 1. Tentukan tahap saat ini & validasi wewenang role
        $stage = match ($disposition->approval_status) {
            Disposition::STATUS_WAITING_MANAGER => DispositionApproval::STAGE_MANAGER,
            Disposition::STATUS_WAITING_FINANCE => DispositionApproval::STAGE_FINANCE,
            Disposition::STATUS_WAITING_OWNER => DispositionApproval::STAGE_OWNER,
            default => null,
        };

        if (!$stage) {
            throw new InvalidArgumentException('Disposisi tidak sedang berada dalam antrean approval aktif.');
        }

        // 2. Validasi Larangan Self-Approval
        // Khusus tahap Finance/Owner: Jika pemohon adalah Finance (misal Indah Novitasari), Finance berwenang
        // mengeksekusi putusan akhir atas arahan Owner setelah diverifikasi oleh Manager.
        $isFinanceFinalStage = in_array($stage, [DispositionApproval::STAGE_FINANCE, DispositionApproval::STAGE_OWNER])
            && ($actor->isAdminFinance() || $actor->isOwner());

        if ($actor->id === $disposition->applicant_id && !$isFinanceFinalStage) {
            throw new InvalidArgumentException('Pemohon tidak diizinkan menyetujui pengajuannya sendiri.');
        }

        // Verifikasi kesesuaian peran
        if ($stage === DispositionApproval::STAGE_MANAGER && !$actor->isManager()) {
            throw new InvalidArgumentException('Hanya Manager yang berwenang memproses persetujuan pada tahap ini.');
        }
        if ($stage === DispositionApproval::STAGE_FINANCE && !($actor->isAdminFinance() || $actor->isOwner())) {
            throw new InvalidArgumentException('Hanya Admin / Finance yang berwenang menentukan status ACC atau TIDAK ACC pada tahap ini berdasarkan arahan Owner.');
        }
        if ($stage === DispositionApproval::STAGE_OWNER && !($actor->isOwner() || $actor->isAdminFinance())) {
            throw new InvalidArgumentException('Hanya Owner / Finance yang berwenang memproses persetujuan final.');
        }

        // 3. Validasi parameter keputusan
        if (!in_array($decision, [
            DispositionApproval::DECISION_APPROVED,
            DispositionApproval::DECISION_PARTIALLY_APPROVED,
            DispositionApproval::DECISION_REVISION_REQUESTED,
            DispositionApproval::DECISION_REJECTED,
        ])) {
            throw new InvalidArgumentException('Keputusan approval tidak valid.');
        }

        if ($decision === DispositionApproval::DECISION_PARTIALLY_APPROVED) {
            if ($approvedAmount === null || $approvedAmount <= 0) {
                throw new InvalidArgumentException('Nominal yang disetujui harus lebih besar dari Rp 0.');
            }
            if ($approvedAmount > (float) $disposition->requested_total) {
                throw new InvalidArgumentException('Nominal disetujui tidak boleh melebihi total nominal pengajuan.');
            }
            if (empty(trim((string)$notes))) {
                throw new InvalidArgumentException('Catatan atau alasan wajib diisi saat menyetujui sebagian nominal.');
            }
        }

        if (in_array($decision, [DispositionApproval::DECISION_REVISION_REQUESTED, DispositionApproval::DECISION_REJECTED])) {
            if (empty(trim((string)$notes))) {
                throw new InvalidArgumentException('Alasan atau catatan wajib diisi saat meminta revisi atau menolak pengajuan.');
            }
        }

        // 4. Eksekusi database transaction dengan lockForUpdate
        return DB::transaction(function () use ($actor, $disposition, $stage, $decision, $approvedAmount, $notes) {
            /** @var Disposition $locked */
            $locked = Disposition::where('id', $disposition->id)->lockForUpdate()->firstOrFail();

            $oldValues = [
                'approval_status' => $locked->approval_status,
                'final_approved_total' => $locked->final_approved_total,
            ];

            $previousAmount = $locked->final_approved_total ?? ($locked->manager_approved_total ?? $locked->requested_total);
            $effectiveApprovedAmount = null;

            switch ($decision) {
                case DispositionApproval::DECISION_APPROVED:
                    $effectiveApprovedAmount = $previousAmount;
                    if ($stage === DispositionApproval::STAGE_MANAGER) {
                        $locked->manager_approved_total = $locked->requested_total;
                        $locked->approval_status = Disposition::STATUS_WAITING_FINANCE;
                    } elseif ($stage === DispositionApproval::STAGE_FINANCE) {
                        // Tahap 2 (Akhir): Finance menentukan status ACC berdasarkan arahan Owner
                        $locked->finance_verified_total = $locked->manager_approved_total ?? $locked->requested_total;
                        $locked->owner_approved_total = $locked->finance_verified_total;
                        $locked->final_approved_total = $locked->finance_verified_total;
                        $locked->approval_status = Disposition::STATUS_APPROVED;
                    } elseif ($stage === DispositionApproval::STAGE_OWNER) {
                        $locked->owner_approved_total = $locked->finance_verified_total ?? $locked->requested_total;
                        $locked->final_approved_total = $locked->owner_approved_total;
                        $locked->approval_status = Disposition::STATUS_APPROVED;
                    }
                    break;

                case DispositionApproval::DECISION_PARTIALLY_APPROVED:
                    $effectiveApprovedAmount = $approvedAmount;
                    if ($stage === DispositionApproval::STAGE_MANAGER) {
                        $locked->manager_approved_total = $approvedAmount;
                        $locked->approval_status = Disposition::STATUS_WAITING_FINANCE;
                    } elseif ($stage === DispositionApproval::STAGE_FINANCE) {
                        // Tahap 2 (Akhir): Finance menentukan nominal ACC berdasarkan arahan Owner
                        $locked->finance_verified_total = $approvedAmount;
                        $locked->owner_approved_total = $approvedAmount;
                        $locked->final_approved_total = $approvedAmount;
                        $locked->approval_status = Disposition::STATUS_PARTIALLY_APPROVED;
                    } elseif ($stage === DispositionApproval::STAGE_OWNER) {
                        $locked->owner_approved_total = $approvedAmount;
                        $locked->final_approved_total = $approvedAmount;
                        $locked->approval_status = Disposition::STATUS_PARTIALLY_APPROVED;
                    }
                    break;

                case DispositionApproval::DECISION_REVISION_REQUESTED:
                    $locked->approval_status = Disposition::STATUS_REVISION_REQUESTED;
                    $locked->revision_notes = $notes;
                    break;

                case DispositionApproval::DECISION_REJECTED:
                    $locked->approval_status = Disposition::STATUS_REJECTED;
                    $locked->rejected_notes = $notes;
                    break;
            }

            $locked->save();

            // Hitung putaran revisi
            $round = $locked->approvals()->where('stage', $stage)->count() + 1;

            $approval = DispositionApproval::create([
                'disposition_id' => $locked->id,
                'stage' => $stage,
                'actor_id' => $actor->id,
                'decision' => $decision,
                'previous_amount' => $previousAmount,
                'approved_amount' => $effectiveApprovedAmount,
                'notes' => $notes,
                'revision_round' => $round,
                'created_at' => now(),
            ]);

            AuditLog::record(
                'disposition_approval_' . $decision,
                $locked,
                $oldValues,
                [
                    'stage' => $stage,
                    'decision' => $decision,
                    'actor' => $actor->name,
                    'approved_amount' => $effectiveApprovedAmount,
                    'notes' => $notes,
                ]
            );

            return $approval;
        });
    }
}
