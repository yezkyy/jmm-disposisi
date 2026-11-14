<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Models\Disposition;
use App\Models\DispositionApproval;
use App\Services\Approval\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {}

    /**
     * Tampilkan Inbox Persetujuan sesuai wewenang role approver.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $tab = $request->input('tab', 'pending'); // pending, history, all

        // Tentukan status yang perlu ditinjau berdasarkan peran pengguna
        $targetStatus = match (true) {
            $user->isManager() => Disposition::STATUS_WAITING_MANAGER,
            $user->isAdminFinance() => Disposition::STATUS_WAITING_FINANCE,
            $user->isOwner() => Disposition::STATUS_WAITING_OWNER,
            default => null,
        };

        // Query Utama
        $query = Disposition::with(['applicant', 'division', 'items', 'recipients'])->latest();

        if ($tab === 'pending') {
            if ($targetStatus) {
                // Jangan tampilkan jika pemohon adalah diri sendiri (self-approval dilarang)
                $query->where('approval_status', $targetStatus)
                      ->where('applicant_id', '!=', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($tab === 'history') {
            // Disposisi yang pernah diputuskan oleh pengguna saat ini
            $query->whereHas('approvals', function ($aq) use ($user) {
                $aq->where('actor_id', $user->id);
            });
        } elseif ($tab === 'all') {
            // Seluruh disposisi yang sedang menunggu proses approval di seluruh jenjang
            $query->whereIn('approval_status', [
                Disposition::STATUS_WAITING_MANAGER,
                Disposition::STATUS_WAITING_FINANCE,
                Disposition::STATUS_WAITING_OWNER,
            ]);
        }

        // Pencarian opsional
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('disposition_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('applicant', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $dispositions = $query->paginate(10)->withQueryString();

        // Hitung total antrean pending untuk badge notifikasi
        $pendingCount = 0;
        if ($targetStatus) {
            $pendingCount = Disposition::where('approval_status', $targetStatus)
                ->where('applicant_id', '!=', $user->id)
                ->count();
        }

        $breadcrumbs = [
            ['label' => 'Alur Persetujuan', 'url' => ''],
            ['label' => 'Kotak Masuk Approval', 'url' => route('approvals.index')],
        ];

        return view('approvals.index', compact('dispositions', 'tab', 'pendingCount', 'targetStatus', 'breadcrumbs'));
    }

    /**
     * Tampilkan layar telaah & form putusan approval untuk disposisi terpilih.
     */
    public function review(Disposition $disposition): View|RedirectResponse
    {
        $user = Auth::user();

        // Cek larangan self-approval
        if ($disposition->applicant_id === $user->id) {
            return redirect()->route('dispositions.show', $disposition)
                ->with('error', 'Anda adalah pemohon pengajuan ini sehingga tidak dapat melakukan approval terhadap pengajuan sendiri.');
        }

        // Cek kesesuaian role dengan status
        $isEligible = match ($disposition->approval_status) {
            Disposition::STATUS_WAITING_MANAGER => $user->isManager(),
            Disposition::STATUS_WAITING_FINANCE => $user->isAdminFinance(),
            Disposition::STATUS_WAITING_OWNER => $user->isOwner(),
            default => false,
        };

        if (!$isEligible) {
            return redirect()->route('dispositions.show', $disposition)
                ->with('error', 'Pengajuan ini tidak sedang berada pada tahap approval yang sesuai dengan wewenang Anda.');
        }

        $disposition->load([
            'applicant.role',
            'division',
            'items.category',
            'recipients.primaryBankAccount',
            'recipients.secondaryBankAccount',
            'approvals.actor.role',
            'attachments.uploader',
        ]);

        $stageName = match ($disposition->approval_status) {
            Disposition::STATUS_WAITING_MANAGER => 'Manager',
            Disposition::STATUS_WAITING_FINANCE => 'Admin Finance',
            Disposition::STATUS_WAITING_OWNER => 'Owner / Direktur',
            default => 'Review',
        };

        $breadcrumbs = [
            ['label' => 'Kotak Masuk Approval', 'url' => route('approvals.index')],
            ['label' => 'Telaah: ' . ($disposition->disposition_number ?: 'Draft #' . $disposition->id), 'url' => ''],
        ];

        return view('approvals.review', compact('disposition', 'stageName', 'breadcrumbs'));
    }

    /**
     * Simpan putusan approval (setujui penuh, setujui sebagian, minta revisi, atau tolak).
     */
    public function decide(Request $request, Disposition $disposition): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,partially_approved,revision_requested,rejected'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->approvalService->processApproval(
                $user,
                $disposition,
                $validated['decision'],
                !empty($validated['approved_amount']) ? (float) $validated['approved_amount'] : null,
                $validated['notes'] ?? null
            );

            $label = match ($validated['decision']) {
                DispositionApproval::DECISION_APPROVED => 'disetujui penuh',
                DispositionApproval::DECISION_PARTIALLY_APPROVED => 'disetujui sebagian nominalnya',
                DispositionApproval::DECISION_REVISION_REQUESTED => 'dikembalikan untuk direvisi oleh pemohon',
                DispositionApproval::DECISION_REJECTED => 'ditolak',
                default => 'diproses',
            };

            return redirect()->route('approvals.index')
                ->with('success', "Disposisi {$disposition->disposition_number} berhasil {$label}.");
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}

