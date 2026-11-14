<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Disposition;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Tampilkan riwayat seluruh transaksi pembayaran.
     */
    public function index(Request $request): View
    {
        $query = Payment::with(['disposition.applicant', 'sourceAccount', 'recipient', 'recorder'])->latest('payment_date');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('disposition', function ($dq) use ($search) {
                      $dq->where('disposition_number', 'like', "%{$search}%")
                         ->orWhere('subject', 'like', "%{$search}%");
                  })
                  ->orWhereHas('recipient', function ($rq) use ($search) {
                      $rq->where('recipient_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($method = $request->input('method')) {
            $query->where('method', $method);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('payment_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('payment_date', '<=', $endDate);
        }

        $payments = $query->paginate(15)->withQueryString();

        // Total akumulasi nominal pembayaran terkonfirmasi (posted)
        $totalPaidSum = Payment::where('status', Payment::STATUS_POSTED)->sum('amount');

        $breadcrumbs = [
            ['label' => 'Modul Pembayaran', 'url' => ''],
            ['label' => 'Riwayat Pembayaran', 'url' => route('payments.index')],
        ];

        return view('payments.index', compact('payments', 'totalPaidSum', 'breadcrumbs'));
    }

    /**
     * Formulir pencatatan pembayaran baru.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $dispositionId = $request->input('disposition_id');
        $selectedDisposition = null;

        if ($dispositionId) {
            $selectedDisposition = Disposition::with(['recipients.primaryBankAccount', 'items'])->find($dispositionId);

            if (!$selectedDisposition) {
                return redirect()->route('payments.index')->with('error', 'Disposisi yang dipilih tidak ditemukan.');
            }

            if (!in_array($selectedDisposition->approval_status, [Disposition::STATUS_APPROVED, Disposition::STATUS_PARTIALLY_APPROVED])) {
                return redirect()->route('dispositions.show', $selectedDisposition)
                    ->with('error', 'Disposisi ini belum disetujui sehingga belum dapat diproses pembayarannya.');
            }

            if ($selectedDisposition->remaining_payment <= 0) {
                return redirect()->route('dispositions.show', $selectedDisposition)
                    ->with('error', 'Disposisi ini sudah lunas dibayarkan.');
            }
        }

        // Ambil daftar disposisi yang siap dibayar
        $payableDispositions = Disposition::whereIn('approval_status', [
                Disposition::STATUS_APPROVED, 
                Disposition::STATUS_PARTIALLY_APPROVED
            ])
            ->whereIn('payment_status', [
                Disposition::PAYMENT_UNPAID, 
                Disposition::PAYMENT_PARTIALLY_PAID
            ])
            ->latest('disposition_date')
            ->get();

        $sourceAccounts = BankAccount::active()->with('bank')->get();

        $breadcrumbs = [
            ['label' => 'Modul Pembayaran', 'url' => route('payments.index')],
            ['label' => 'Catat Pembayaran Baru', 'url' => ''],
        ];

        return view('payments.create', compact('selectedDisposition', 'payableDispositions', 'sourceAccounts', 'breadcrumbs'));
    }

    /**
     * Simpan transaksi pembayaran baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'disposition_id' => ['required', 'exists:dispositions,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:transfer,cash,other'],
            'source_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'recipient_id' => ['nullable', 'exists:disposition_recipients,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof_file' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ], [
            'disposition_id.required' => 'Pilih disposisi yang hendak dibayar.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.min' => 'Nominal pembayaran minimal Rp 1.',
        ]);

        $disposition = Disposition::findOrFail($validated['disposition_id']);
        $proofFile = $request->file('proof_file');

        try {
            $payment = $this->paymentService->recordPayment(
                Auth::user(),
                $disposition,
                $validated,
                $proofFile
            );

            return redirect()->route('payments.show', $payment)
                ->with('success', "Pembayaran nomor {$payment->payment_number} berhasil dicatat sebesar Rp " . number_format($payment->amount, 2, ',', '.') . ".");
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Tampilkan rincian voucher pembayaran.
     */
    public function show(Payment $payment): View
    {
        $payment->load([
            'disposition.applicant',
            'disposition.division',
            'disposition.recipients',
            'sourceAccount',
            'recipient',
            'recorder',
            'reverser',
        ]);

        $breadcrumbs = [
            ['label' => 'Modul Pembayaran', 'url' => route('payments.index')],
            ['label' => $payment->payment_number, 'url' => ''],
        ];

        return view('payments.show', compact('payment', 'breadcrumbs'));
    }

    /**
     * Batalkan pembayaran (Reversal).
     */
    public function reverse(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:1000'],
        ], [
            'reversal_reason.required' => 'Alasan pembatalan pembayaran wajib diisi.',
        ]);

        try {
            $this->paymentService->reversePayment(
                Auth::user(),
                $payment,
                $validated['reversal_reason']
            );

            return redirect()->route('payments.show', $payment)
                ->with('success', "Pembayaran {$payment->payment_number} berhasil dibatalkan (reversed). Status disposisi telah dipulihkan.");
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

