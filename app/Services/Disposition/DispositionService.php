<?php

namespace App\Services\Disposition;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\Disposition;
use App\Models\DispositionItem;
use App\Models\DispositionRecipient;
use App\Models\DispositionSequence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DispositionService
{
    /**
     * Simpan pengajuan disposisi baru (bisa sebagai draft atau langsung submit).
     */
    public function createDisposition(User $user, array $data, bool $isDraft = false): Disposition
    {
        return DB::transaction(function () use ($user, $data, $isDraft) {
            $year = (int) date('Y', strtotime($data['disposition_date'] ?? 'now'));

            $dispositionNumber = null;
            $status = Disposition::STATUS_DRAFT;
            $submittedAt = null;

            if (!$isDraft) {
                $dispositionNumber = DispositionSequence::generateDispositionNumber($year);
                $status = Disposition::STATUS_WAITING_MANAGER;
                $submittedAt = now();
            }

            $disposition = Disposition::create([
                'disposition_number' => $dispositionNumber,
                'disposition_date' => $data['disposition_date'],
                'subject' => $data['subject'],
                'applicant_id' => $user->id,
                'division_id' => $data['division_id'] ?? $user->division_id,
                'description' => $data['description'] ?? null,
                'approval_status' => $status,
                'payment_status' => Disposition::PAYMENT_UNPAID,
                'requested_total' => 0, // Akan dihitung dari items
                'submitted_at' => $submittedAt,
            ]);

            $total = $this->syncItemsAndRecipients($disposition, $data);

            $disposition->requested_total = $total;
            $disposition->save();

            // Simpan lampiran bila ada
            if (!empty($data['attachments'])) {
                $this->storeAttachments($disposition, $data['attachments'], $user->id);
            }

            AuditLog::record(
                $isDraft ? 'disposition_draft_created' : 'disposition_submitted',
                $disposition,
                [],
                [
                    'disposition_number' => $disposition->disposition_number,
                    'subject' => $disposition->subject,
                    'total' => $disposition->requested_total,
                ]
            );

            return $disposition;
        });
    }

    /**
     * Perbarui draft atau pengajuan yang perlu revisi.
     */
    public function updateDisposition(Disposition $disposition, array $data, bool $isSubmit = false): Disposition
    {
        return DB::transaction(function () use ($disposition, $data, $isSubmit) {
            $oldValues = $disposition->toArray();

            $year = (int) date('Y', strtotime($data['disposition_date'] ?? 'now'));

            // Jika sebelumnya draft dan sekarang disubmit, buat nomor disposisi
            if ($isSubmit && empty($disposition->disposition_number)) {
                $disposition->disposition_number = DispositionSequence::generateDispositionNumber($year);
            }

            if ($isSubmit) {
                $disposition->approval_status = Disposition::STATUS_WAITING_MANAGER;
                $disposition->submitted_at = now();
            }

            $disposition->disposition_date = $data['disposition_date'];
            $disposition->subject = $data['subject'];
            $disposition->division_id = $data['division_id'] ?? $disposition->division_id;
            $disposition->description = $data['description'] ?? null;

            // Hapus items & recipients lama dan gantikan dengan data baru
            $disposition->items()->delete();
            $disposition->recipients()->delete();

            $total = $this->syncItemsAndRecipients($disposition, $data);

            $disposition->requested_total = $total;
            $disposition->save();

            // Simpan lampiran tambahan bila ada
            if (!empty($data['attachments'])) {
                $this->storeAttachments($disposition, $data['attachments'], $disposition->applicant_id);
            }

            AuditLog::record(
                $isSubmit ? 'disposition_submitted' : 'disposition_updated',
                $disposition,
                $oldValues,
                [
                    'disposition_number' => $disposition->disposition_number,
                    'subject' => $disposition->subject,
                    'total' => $disposition->requested_total,
                ]
            );

            return $disposition;
        });
    }

    /**
     * Submit draft disposisi yang sudah ada.
     */
    public function submitDraft(Disposition $disposition): Disposition
    {
        return DB::transaction(function () use ($disposition) {
            $year = (int) date('Y', strtotime($disposition->disposition_date));

            if (empty($disposition->disposition_number)) {
                $disposition->disposition_number = DispositionSequence::generateDispositionNumber($year);
            }

            $disposition->approval_status = Disposition::STATUS_WAITING_MANAGER;
            $disposition->submitted_at = now();
            $disposition->save();

            AuditLog::record('disposition_submitted', $disposition, [], [
                'disposition_number' => $disposition->disposition_number,
                'total' => $disposition->requested_total,
            ]);

            return $disposition;
        });
    }

    /**
     * Simpan rincian pengeluaran dan daftar penerima.
     */
    protected function syncItemsAndRecipients(Disposition $disposition, array $data): float
    {
        $calculatedTotal = 0.0;

        // 1. Simpan Items
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $cleanAmount = (float) str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], (string)($item['amount'] ?? 0));
                $calculatedTotal += $cleanAmount;

                $desc = $item['description'] ?? ($item['item_name'] ?? '-');
                if (!empty($item['quantity']) && !empty($item['unit_price']) && empty($item['description'])) {
                    $desc = ($item['item_name'] ?? 'Item') . ' (' . $item['quantity'] . ' ' . ($item['unit'] ?? 'Unit') . ' @ ' . number_format((float)$item['unit_price'], 0, ',', '.') . ')';
                }

                DispositionItem::create([
                    'disposition_id' => $disposition->id,
                    'category_id' => $item['category_id'] ?? Category::active()->first()?->id,
                    'description' => $desc,
                    'amount' => $cleanAmount,
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        }

        // 2. Simpan Penerima
        if (!empty($data['recipients']) && is_array($data['recipients'])) {
            foreach ($data['recipients'] as $rec) {
                // Rekening 1 (Wajib)
                $primaryAccount = null;
                if (!empty($rec['primary_bank_account_id'])) {
                    $primaryAccount = BankAccount::find($rec['primary_bank_account_id']);
                }

                $primaryName = $primaryAccount?->owner_name ?? ($rec['primary_account_holder'] ?? ($rec['primary_account_name'] ?? '-'));
                $primaryBank = $primaryAccount?->bank_name ?? ($rec['primary_bank_name'] ?? '-');
                $primaryNumber = $primaryAccount?->account_number ?? ($rec['primary_account_number'] ?? '-');

                // Rekening 2 (Opsional)
                $secondaryAccount = null;
                $secondaryName = null;
                $secondaryBank = null;
                $secondaryNumber = null;

                if (!empty($rec['secondary_bank_account_id'])) {
                    $secondaryAccount = BankAccount::find($rec['secondary_bank_account_id']);
                    if ($secondaryAccount) {
                        $secondaryName = $secondaryAccount->owner_name;
                        $secondaryBank = $secondaryAccount->bank_name;
                        $secondaryNumber = $secondaryAccount->account_number;
                    }
                } elseif (!empty($rec['secondary_account_number'])) {
                    $secondaryName = $rec['secondary_account_holder'] ?? ($rec['secondary_account_name'] ?? null);
                    $secondaryBank = $rec['secondary_bank_name'] ?? null;
                    $secondaryNumber = $rec['secondary_account_number'];
                }

                $rawAlloc = $rec['allocated_amount'] ?? ($rec['amount'] ?? 0);
                $allocAmount = (float) str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], (string)$rawAlloc);

                DispositionRecipient::create([
                    'disposition_id' => $disposition->id,
                    'recipient_name' => $rec['recipient_name'],
                    'recipient_type' => $rec['recipient_type'] ?? 'employee',
                    'primary_bank_account_id' => $primaryAccount?->id,
                    'primary_account_name' => $primaryName,
                    'primary_bank_name' => $primaryBank,
                    'primary_account_number' => (string) $primaryNumber,
                    'secondary_bank_account_id' => $secondaryAccount?->id,
                    'secondary_account_name' => $secondaryName,
                    'secondary_bank_name' => $secondaryBank,
                    'secondary_account_number' => $secondaryNumber ? (string) $secondaryNumber : null,
                    'allocated_amount' => $allocAmount,
                    'notes' => $rec['notes'] ?? null,
                ]);
            }
        }

        return $calculatedTotal;
    }

    /**
     * Simpan file lampiran pengajuan secara aman.
     */
    protected function storeAttachments(Disposition $disposition, array $files, int $uploadedBy): void
    {
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $file->store('attachments/dispositions', 'public');

                Attachment::create([
                    'attachable_type' => Disposition::class,
                    'attachable_id' => $disposition->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getClientMimeType(),
                    'uploaded_by' => $uploadedBy,
                ]);
            }
        }
    }
}
