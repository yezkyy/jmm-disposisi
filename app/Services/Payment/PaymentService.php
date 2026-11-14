<?php

namespace App\Services\Payment;

use App\Models\AuditLog;
use App\Models\Disposition;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Catat pembayaran baru untuk disposisi yang telah disetujui.
     *
     * @throws InvalidArgumentException
     */
    public function recordPayment(
        User $recorder,
        Disposition $disposition,
        array $data,
        ?UploadedFile $proofFile = null
    ): Payment {
        // 1. Verifikasi wewenang peran
        if (!$recorder->isAdminFinance() && !$recorder->isOwner()) {
            throw new InvalidArgumentException('Hanya Admin Finance atau Owner yang memiliki wewenang mencatat pembayaran.');
        }

        // 2. Transaksi database dengan lockForUpdate untuk mencegah race condition & overpayment
        return DB::transaction(function () use ($recorder, $disposition, $data, $proofFile) {
            /** @var Disposition $locked */
            $locked = Disposition::where('id', $disposition->id)->lockForUpdate()->firstOrFail();

            // Cek status persetujuan disposisi
            if (!in_array($locked->approval_status, [Disposition::STATUS_APPROVED, Disposition::STATUS_PARTIALLY_APPROVED])) {
                throw new InvalidArgumentException('Pembayaran hanya dapat dicatat untuk disposisi yang telah disetujui penuh atau disetujui sebagian.');
            }

            $amount = (float) str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], (string)($data['amount'] ?? 0));
            if ($amount <= 0) {
                throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari Rp 0.');
            }

            // Hitung sisa kewajiban pembayaran saat ini
            $approvedTotal = (float) ($locked->final_approved_total ?? $locked->requested_total);
            $currentPaid = (float) $locked->payments()->where('status', Payment::STATUS_POSTED)->sum('amount');
            $remaining = $approvedTotal - $currentPaid;

            // Validasi pencegahan overpayment (toleransi selisih floating point 0.01)
            if ($amount > ($remaining + 0.01)) {
                $remFormatted = number_format($remaining, 2, ',', '.');
                $amtFormatted = number_format($amount, 2, ',', '.');
                throw new InvalidArgumentException("Nominal pembayaran (Rp {$amtFormatted}) melebihi sisa tagihan yang belum dibayar (Rp {$remFormatted}).");
            }

            // Simpan bukti transfer bila ada
            $proofPath = null;
            $proofName = null;
            if ($proofFile && $proofFile->isValid()) {
                $proofName = $proofFile->getClientOriginalName();
                $proofPath = $proofFile->store('payments/proofs', 'public');
            }

            // Buat record pembayaran
            $paymentNumber = Payment::generatePaymentNumber();

            $payment = Payment::create([
                'disposition_id' => $locked->id,
                'payment_number' => $paymentNumber,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'method' => $data['method'] ?? Payment::METHOD_TRANSFER,
                'source_account_id' => $data['source_account_id'] ?? null,
                'recipient_id' => $data['recipient_id'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'proof_path' => $proofPath,
                'proof_name' => $proofName,
                'recorded_by' => $recorder->id,
                'status' => Payment::STATUS_POSTED,
            ]);

            // Sinkronisasi status pembayaran disposisi
            $newPaid = $currentPaid + $amount;
            $locked->paid_total = $newPaid;

            if ($newPaid >= ($approvedTotal - 0.01)) {
                $locked->payment_status = Disposition::PAYMENT_PAID;
            } else {
                $locked->payment_status = Disposition::PAYMENT_PARTIALLY_PAID;
            }

            $locked->save();

            AuditLog::record('payment_recorded', $payment, [], [
                'payment_number' => $paymentNumber,
                'disposition_number' => $locked->disposition_number,
                'amount' => $amount,
                'remaining' => $approvedTotal - $newPaid,
            ]);

            return $payment;
        });
    }

    /**
     * Batalkan pembayaran yang salah catat (Reversal) dan pulihkan status disposisi.
     *
     * @throws InvalidArgumentException
     */
    public function reversePayment(User $reverser, Payment $payment, string $reason): Payment
    {
        if (!$reverser->isAdminFinance() && !$reverser->isOwner()) {
            throw new InvalidArgumentException('Hanya Admin Finance atau Owner yang berwenang membatalkan pembayaran.');
        }

        if ($payment->status === Payment::STATUS_REVERSED) {
            throw new InvalidArgumentException('Pembayaran ini sudah pernah dibatalkan sebelumnya.');
        }

        if (empty(trim($reason))) {
            throw new InvalidArgumentException('Alasan pembatalan (reversal) pembayaran wajib dicantumkan.');
        }

        return DB::transaction(function () use ($reverser, $payment, $reason) {
            /** @var Disposition $lockedDisp */
            $lockedDisp = Disposition::where('id', $payment->disposition_id)->lockForUpdate()->firstOrFail();

            $oldPaymentValues = $payment->toArray();

            $payment->status = Payment::STATUS_REVERSED;
            $payment->reversed_at = now();
            $payment->reversed_by = $reverser->id;
            $payment->reversal_reason = $reason;
            $payment->save();

            // Hitung ulang akumulasi pembayaran aktif
            $approvedTotal = (float) ($lockedDisp->final_approved_total ?? $lockedDisp->requested_total);
            $activePaid = (float) $lockedDisp->payments()->where('status', Payment::STATUS_POSTED)->sum('amount');
            $lockedDisp->paid_total = $activePaid;

            if ($activePaid >= ($approvedTotal - 0.01) && $approvedTotal > 0) {
                $lockedDisp->payment_status = Disposition::PAYMENT_PAID;
            } elseif ($activePaid > 0) {
                $lockedDisp->payment_status = Disposition::PAYMENT_PARTIALLY_PAID;
            } else {
                $lockedDisp->payment_status = Disposition::PAYMENT_UNPAID;
            }

            $lockedDisp->save();

            AuditLog::record('payment_reversed', $payment, $oldPaymentValues, [
                'reversal_reason' => $reason,
                'reversed_by' => $reverser->name,
                'disposition_number' => $lockedDisp->disposition_number,
                'recalculated_paid_total' => $activePaid,
            ]);

            return $payment;
        });
    }
}

