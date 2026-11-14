@extends('layouts.app')

@section('title', 'Voucher Pembayaran: ' . $payment->payment_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="receipt" class="w-6 h-6 text-primary-600"></i>
                Voucher Pengeluaran Dana
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Bukti kas/bank keluar resmi PT Jaya Mukti Mandiri.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('payments.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Riwayat Pembayaran</span>
            </a>
            <a href="{{ route('dispositions.show', $payment->disposition_id) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary-50 text-primary-700 hover:bg-primary-100 text-xs font-semibold transition">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Ke Disposisi</span>
            </a>
        </div>
    </div>

    <!-- Banner Reversal Jika Status Reversed -->
    @if ($payment->status === \App\Models\Payment::STATUS_REVERSED)
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start gap-3">
            <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5"></i>
            <div class="text-xs space-y-1">
                <p class="font-bold text-sm">Pembayaran Telah Dibatalkan (Reversed)</p>
                <p>Transaksi ini dibatalkan oleh <strong>{{ $payment->reverser?->name }}</strong> pada {{ $payment->reversed_at?->format('d/m/Y H:i') }}.</p>
                <p class="italic text-rose-700">"{{ $payment->reversal_reason }}"</p>
            </div>
        </div>
    @endif

    <!-- THE CORPORATE VOUCHER DOCUMENT -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
        <!-- Voucher Header -->
        <div class="p-6 border-b border-slate-200 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-white p-1 border border-slate-200 shadow-sm flex items-center justify-center flex-shrink-0">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo JMM" class="w-full h-full object-contain">
                </div>
                <div>
                    <h2 class="text-sm font-extrabold uppercase text-slate-900 tracking-wider">PT JAYA MUKTI MANDIRI</h2>
                    <p class="text-[11px] text-slate-500">Bukti Pengeluaran Kas & Bank (Payment Voucher)</p>
                </div>
            </div>

            <div class="text-right">
                <div class="flex items-center justify-end gap-2">
                    <span class="text-xs text-slate-400 font-semibold uppercase">No. Voucher:</span>
                    <span class="font-mono font-extrabold text-slate-800 text-sm">{{ $payment->payment_number }}</span>
                </div>
                <div class="mt-1">
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                        @if($payment->status === 'posted') bg-emerald-100 text-emerald-800
                        @elseif($payment->status === 'reversed') bg-rose-100 text-rose-800
                        @else bg-slate-100 text-slate-700 @endif">
                        {{ strtoupper($payment->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Voucher Body -->
        <div class="p-6 space-y-6 text-xs">
            <!-- Informasi Pokok -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-4 border-b border-slate-100">
                <div class="space-y-3">
                    <a href="{{ route('dispositions.show', $payment->disposition_id) }}" class="group block p-3 rounded-xl bg-slate-50 hover:bg-primary-50/50 border border-slate-200/80 hover:border-primary-300 transition cursor-pointer">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400 group-hover:text-primary-600 block text-[10px] uppercase font-bold transition-colors">Disposisi Rujukan</span>
                            <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-slate-400 group-hover:text-primary-600 transition-colors"></i>
                        </div>
                        <span class="font-mono font-bold text-primary-600 group-hover:text-primary-700 block mt-0.5">
                            {{ $payment->disposition?->disposition_number }}
                        </span>
                        <p class="text-slate-600 text-xs mt-0.5 font-medium truncate">{{ $payment->disposition?->subject }}</p>
                    </a>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Pemohon / Divisi:</span>
                        <span class="font-semibold text-slate-700">{{ $payment->disposition?->applicant?->name }} ({{ $payment->disposition?->division?->name ?? 'Tanpa Divisi' }})</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Tanggal Realisasi Pembayaran:</span>
                        <span class="font-bold text-slate-800">{{ $payment->payment_date ? $payment->payment_date->translatedFormat('d F Y') : '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Metode Pencairan:</span>
                        <span class="font-semibold text-slate-700 uppercase">{{ $payment->method_label }}</span>
                    </div>
                    @if ($payment->reference_number)
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">No. Referensi Bank / Struk:</span>
                            <span class="font-mono font-bold text-slate-800">{{ $payment->reference_number }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Rekening Sumber & Penerima -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Rekening Sumber PT JMM -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Dibayarkan Dari (Rekening Sumber):</span>
                    @if ($payment->sourceAccount)
                        <div class="font-bold text-slate-800 text-sm">{{ $payment->sourceAccount->bank_name }}</div>
                        <div class="font-mono font-bold text-primary-700">{{ $payment->sourceAccount->account_number }}</div>
                        <div class="text-[11px] text-slate-500">a.n. {{ $payment->sourceAccount->owner_name }}</div>
                    @else
                        <div class="font-bold text-slate-800">Kas Tunai Perusahaan (Petty Cash)</div>
                    @endif
                </div>

                <!-- Rekening Penerima -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Dibayarkan Kepada (Penerima):</span>
                    @if ($payment->recipient)
                        <div class="font-bold text-slate-800 text-sm">{{ $payment->recipient->recipient_name }}</div>
                        <div class="font-mono font-bold text-emerald-700">{{ $payment->recipient->primary_bank_name }} - {{ $payment->recipient->primary_account_number }}</div>
                        <div class="text-[11px] text-slate-500">a.n. {{ $payment->recipient->primary_account_holder }}</div>
                    @else
                        <div class="font-bold text-slate-800">Penerima Disposisi</div>
                        <div class="text-[11px] text-slate-500">Sesuai rincian penerima pada dokumen disposisi</div>
                    @endif
                </div>
            </div>

            <!-- Nominal Voucher Box -->
            <div class="p-5 rounded-xl bg-emerald-50 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <span class="text-[10px] font-extrabold uppercase text-emerald-800 tracking-wider block">
                        Jumlah Dana Yang Dibayarkan
                    </span>
                    <span class="text-[11px] text-emerald-700 mt-0.5 block">
                        Diverifikasi dan dicatat oleh <strong>{{ $payment->recorder?->name }}</strong>
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-extrabold text-emerald-800 font-mono">
                        Rp {{ number_format($payment->amount, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Catatan Kasir / Finance -->
            @if ($payment->notes)
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold mb-1">Catatan Realisasi:</span>
                    <p class="p-3 bg-slate-50 rounded-xl text-slate-600 border border-slate-100 whitespace-pre-line">{{ $payment->notes }}</p>
                </div>
            @endif

            <!-- Bukti Transfer Lampiran -->
            @if ($payment->proof_path)
                <div class="pt-4 border-t border-slate-100">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold mb-2">Bukti Transfer / Nota Struk:</span>
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50 max-w-md">
                        <i data-lucide="file-check" class="w-6 h-6 text-primary-600 flex-shrink-0"></i>
                        <div class="min-w-0 flex-1">
                            <span class="font-semibold text-slate-800 truncate block">{{ $payment->proof_name ?: 'Bukti Pembayaran' }}</span>
                            <span class="text-[10px] text-slate-400">Berkas verifikasi transfer</span>
                        </div>
                        <a href="{{ asset('storage/' . $payment->proof_path) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-primary-600 hover:text-primary-700 font-bold transition flex items-center gap-1 shadow-sm">
                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            <span>Buka</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Voucher Footer / Tombol Reversal -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
            <span class="text-slate-400 text-[11px]">
                Dicatat: {{ $payment->created_at->format('d/m/Y H:i') }}
            </span>

            @if ($payment->status === \App\Models\Payment::STATUS_POSTED && (auth()->user()->isAdminFinance() || auth()->user()->isOwner()))
                <button type="button" onclick="openReversalModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold transition">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Batalkan Pembayaran (Reversal)</span>
                </button>
            @endif
        </div>
    </div>
</div>

<!-- Modal Reversal Pembayaran -->
@if ($payment->status === \App\Models\Payment::STATUS_POSTED)
<div id="reversal-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-rose-50 border-b border-rose-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-rose-800 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>
                Batalkan Pembayaran (Reversal)
            </h3>
            <button type="button" onclick="closeReversalModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('payments.reverse', $payment) }}" class="p-6 space-y-4">
            @csrf
            <p class="text-xs text-slate-600">
                Pembatalan akan mengubah status voucher menjadi <strong>REVERSED</strong> dan mengurangi akumulasi pembayaran disposisi terkait secara otomatis.
            </p>

            <div>
                <label for="reversal_reason" class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Alasan Pembatalan <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    id="reversal_reason" 
                    name="reversal_reason" 
                    rows="3" 
                    required 
                    placeholder="Contoh: Salah input nomor rekening / transfer ganda / dana ditolak bank..." 
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-rose-500"
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeReversalModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm">
                    Konfirmasi Reversal
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    function openReversalModal() {
        const modal = document.getElementById('reversal-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeReversalModal() {
        const modal = document.getElementById('reversal-modal');
        if (modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    }
</script>
@endpush

