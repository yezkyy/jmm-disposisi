<?php

namespace App\Http\Controllers\Disposition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Disposition\StoreDispositionRequest;
use App\Http\Requests\Disposition\UpdateDispositionRequest;
use App\Models\AuditLog;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\DailyTransferProof;
use App\Models\Disposition;
use App\Models\Division;
use App\Models\User;
use App\Services\Disposition\DispositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DispositionController extends Controller
{
    public function __construct(
        protected DispositionService $dispositionService
    ) {}

    /**
     * Tampilkan daftar disposisi dengan pencarian, filter, dan pagination.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Disposition::with(['applicant', 'division', 'items.category', 'recipients'])->latest('disposition_date');

        // Scoping akses data berdasarkan peran
        if ($user->isPemohon()) {
            // Pemohon hanya melihat disposisi miliknya sendiri
            $query->where('applicant_id', $user->id);
        }

        // Pencarian nomor, perihal, atau deskripsi
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('disposition_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('recipients', function ($rq) use ($search) {
                      $rq->where('recipient_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter status approval
        if ($status = $request->input('approval_status')) {
            $query->where('approval_status', $status);
        }

        // Filter status pembayaran
        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        // Filter divisi
        if ($divisionId = $request->input('division_id')) {
            $query->where('division_id', $divisionId);
        }

        // Filter pemohon
        if ($applicantId = $request->input('applicant_id')) {
            $query->where('applicant_id', $applicantId);
        }

        // Filter kategori
        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('items', function ($iq) use ($categoryId) {
                $iq->where('category_id', $categoryId);
            });
        }

        // Filter rentang tanggal
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('disposition_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('disposition_date', '<=', $endDate);
        }

        $dispositions = $query->paginate(10)->withQueryString();

        // Metrik Disposisi Hari Ini (Revisi Versi 3 Poin 7)
        $todayDate = now()->toDateString();
        $todayDispositionsQuery = Disposition::whereDate('disposition_date', $todayDate);
        if ($user->isPemohon()) {
            $todayDispositionsQuery->where('applicant_id', $user->id);
        }

        $todayCount = (clone $todayDispositionsQuery)->count();
        $todayNominal = (float) (clone $todayDispositionsQuery)->sum('requested_total');
        $todayApproved = (float) (clone $todayDispositionsQuery)->whereIn('approval_status', [
            Disposition::STATUS_APPROVED,
            Disposition::STATUS_PARTIALLY_APPROVED
        ])->sum(DB::raw('COALESCE(final_approved_total, requested_total)'));

        // Bukti Transfer Harian (Revisi Versi 3 Poin 8)
        $dailyTransferProofs = DailyTransferProof::with('uploader')
            ->latest('transfer_date')
            ->take(30)
            ->get();

        $divisions = Division::active()->get();
        $categories = Category::active()->get();
        $applicants = User::whereHas('role', function ($rq) {
            $rq->where('name', 'pemohon');
        })->get();

        $breadcrumbs = [
            ['label' => 'Modul Disposisi', 'url' => ''],
            ['label' => 'Daftar Disposisi', 'url' => route('dispositions.index')],
        ];

        return view('dispositions.index', compact(
            'dispositions',
            'divisions',
            'categories',
            'applicants',
            'breadcrumbs',
            'todayCount',
            'todayNominal',
            'todayApproved',
            'dailyTransferProofs'
        ));
    }

    /**
     * Formulir pengajuan disposisi baru.
     */
    public function create(): View
    {
        $categories = Category::active()->get();
        $divisions = Division::active()->get();
        $bankAccounts = BankAccount::active()->with('bank')->get();
        $banks = Bank::active()->get();

        $breadcrumbs = [
            ['label' => 'Modul Disposisi', 'url' => route('dispositions.index')],
            ['label' => 'Buat Disposisi Baru', 'url' => ''],
        ];

        return view('dispositions.create', compact('categories', 'divisions', 'bankAccounts', 'banks', 'breadcrumbs'));
    }

    /**
     * Simpan pengajuan disposisi baru (sebagai draft atau langsung submit).
     */
    public function store(StoreDispositionRequest $request): RedirectResponse
    {
        $isDraft = $request->input('action') === 'draft';
        $disposition = $this->dispositionService->createDisposition(Auth::user(), $request->validated(), $isDraft);

        $msg = $isDraft
            ? "Draft disposisi '{$disposition->subject}' berhasil disimpan."
            : "Pengajuan disposisi nomor {$disposition->disposition_number} berhasil dikirim ke antrean approval Manager.";

        return redirect()->route('dispositions.show', $disposition)->with('success', $msg);
    }

    /**
     * Tampilkan detail lengkap disposisi.
     */
    public function show(Disposition $disposition): View
    {
        $user = Auth::user();

        // Otorisasi: Pemohon hanya bisa melihat disposisi miliknya
        if ($user->isPemohon() && $disposition->applicant_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat pengajuan ini.');
        }

        $disposition->load([
            'applicant.role',
            'division',
            'items.category',
            'recipients.primaryBankAccount',
            'recipients.secondaryBankAccount',
            'approvals.actor.role',
            'payments.sourceAccount',
            'payments.recorder',
            'attachments.uploader',
        ]);

        $breadcrumbs = [
            ['label' => 'Modul Disposisi', 'url' => route('dispositions.index')],
            ['label' => $disposition->disposition_number ?: 'Draft #' . $disposition->id, 'url' => ''],
        ];

        return view('dispositions.show', compact('disposition', 'breadcrumbs'));
    }

    /**
     * Formulir edit draft atau revisi disposisi.
     */
    public function edit(Disposition $disposition): View|RedirectResponse
    {
        if (!$disposition->canBeEditedBy(Auth::user())) {
            return redirect()->route('dispositions.show', $disposition)
                ->with('error', 'Disposisi ini tidak dapat diedit karena sedang dalam proses approval atau sudah disetujui.');
        }

        $disposition->load(['items', 'recipients', 'attachments']);

        $categories = Category::active()->get();
        $divisions = Division::active()->get();
        $bankAccounts = BankAccount::active()->with('bank')->get();
        $banks = Bank::active()->get();

        $breadcrumbs = [
            ['label' => 'Modul Disposisi', 'url' => route('dispositions.index')],
            ['label' => 'Edit: ' . ($disposition->disposition_number ?: 'Draft #' . $disposition->id), 'url' => ''],
        ];

        return view('dispositions.edit', compact('disposition', 'categories', 'divisions', 'bankAccounts', 'banks', 'breadcrumbs'));
    }

    /**
     * Perbarui pengajuan disposisi.
     */
    public function update(UpdateDispositionRequest $request, Disposition $disposition): RedirectResponse
    {
        $isSubmit = $request->input('action') === 'submit';
        $updated = $this->dispositionService->updateDisposition($disposition, $request->validated(), $isSubmit);

        $msg = $isSubmit
            ? "Pengajuan disposisi {$updated->disposition_number} berhasil dikirim ulang ke tahap approval Manager."
            : "Draft disposisi berhasil diperbarui.";

        return redirect()->route('dispositions.show', $updated)->with('success', $msg);
    }

    /**
     * Submit draft pengajuan menjadi aktif dalam antrean approval.
     */
    public function submit(Disposition $disposition): RedirectResponse
    {
        if ($disposition->applicant_id !== Auth::id() || $disposition->approval_status !== Disposition::STATUS_DRAFT) {
            abort(403, 'Aksi tidak diizinkan untuk pengajuan ini.');
        }

        $submitted = $this->dispositionService->submitDraft($disposition);

        return redirect()->route('dispositions.show', $submitted)
            ->with('success', "Disposisi nomor {$submitted->disposition_number} berhasil disubmit ke antrean approval Manager.");
    }

    /**
     * Hapus data disposisi.
     * Admin/Finance dapat menghapus data disposisi.
     * Pemohon hanya dapat menghapus draft miliknya sendiri.
     * Manager tidak memiliki akses hapus.
     */
    public function destroy(Disposition $disposition): RedirectResponse
    {
        $user = Auth::user();

        if (!$disposition->canBeDeletedBy($user)) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data disposisi ini.');
        }

        $subject = $disposition->subject;
        $num = $disposition->disposition_number ?: 'Draft #' . $disposition->id;
        $old = $disposition->toArray();

        DB::transaction(function () use ($disposition) {
            // Hapus berkas fisik lampiran dan nota jika tersimpan
            foreach ($disposition->attachments as $att) {
                if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                    Storage::disk('public')->delete($att->file_path);
                }
            }
            foreach ($disposition->items as $item) {
                if ($item->receipt_path && Storage::disk('public')->exists($item->receipt_path)) {
                    Storage::disk('public')->delete($item->receipt_path);
                }
            }

            // Hapus relasi anak secara eksplisit agar aman dari Foreign Key constraint (FK 1451)
            $disposition->attachments()->delete();
            $disposition->payments()->delete();
            $disposition->approvals()->delete();
            $disposition->items()->delete();
            $disposition->recipients()->delete();

            $disposition->delete();
        });

        AuditLog::record('disposition_deleted', null, $old, []);

        return redirect()->route('dispositions.index')
            ->with('success', "Disposisi '{$num} - {$subject}' berhasil dihapus.");
    }
}

