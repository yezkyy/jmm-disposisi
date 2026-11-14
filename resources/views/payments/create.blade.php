@extends('layouts.app')

@section('title', 'Catat Pembayaran Disposisi')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="credit-card" class="w-6 h-6 text-primary-600"></i>
                Catat Pembayaran Disposisi
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Catat realisasi pencairan dana, voucher pengeluaran bank/kas, dan upload bukti transfer.
            </p>
        </div>
        <a href="{{ route('payments.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Riwayat</span>
        </a>
    </div>

    <!-- Pilihan Disposisi Jika Belum Terpilih -->
    @if (!$selectedDisposition)
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="file-search" class="w-4 h-4 text-primary-600"></i>
                Langkah 1: Pilih Disposisi Yang Siap Dibayar
            </h2>

            <form method="GET" action="{{ route('payments.create') }}" class="space-y-4">
                <div>
                    <label for="disposition_id" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Pilih Pengajuan Disposisi <span class="text-rose-500">*</span>
                    </label>
                    <select id="disposition_id" name="disposition_id" required onchange="this.form.submit()" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 font-medium">
                        <option value="">-- Pilih Disposisi yang telah disetujui --</option>
                        @foreach ($payableDispositions as $disp)
                            <option value="{{ $disp->id }}">
                                {{ $disp->disposition_number }} &bull; {{ $disp->subject }} &bull; Sisa: Rp {{ number_format($disp->remaining_payment, 2, ',', '.') }} ({{ $disp->applicant?->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    @else
        <!-- Kartu Ringkasan Disposisi Terpilih -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                        Disposisi Disetujui
                    </span>
                    <h2 class="text-base font-extrabold text-slate-800 mt-1">
                        {{ $selectedDisposition->disposition_number }} &mdash; {{ $selectedDisposition->subject }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pemohon: <strong>{{ $selectedDisposition->applicant?->name }}</strong> ({{ $selectedDisposition->division?->name ?? 'Tanpa Divisi' }}) &bull; Tanggal: {{ $selectedDisposition->disposition_date?->format('d/m/Y') }}
                    </p>
                </div>
                <a href="{{ route('payments.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Ganti Disposisi</span>
                </a>
            </div>

            <!-- 3 Kolom Finansial Disposisi -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Total Disetujui:</span>
                    <span class="text-base font-extrabold text-slate-800 font-mono">
                        Rp {{ number_format($selectedDisposition->final_approved_total ?? $selectedDisposition->requested_total, 2, ',', '.') }}
                    </span>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Sudah Dibayar:</span>
                    <span class="text-base font-extrabold text-primary-700 font-mono">
                        Rp {{ number_format($selectedDisposition->paid_total, 2, ',', '.') }}
                    </span>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs">
                    <span class="text-emerald-700 block text-[10px] uppercase font-bold">Sisa Belum Dibayar:</span>
                    <span id="remaining-amount-display" class="text-base font-extrabold text-emerald-700 font-mono">
                        Rp {{ number_format($selectedDisposition->remaining_payment, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- FORM PENCATATAN PEMBAYARAN -->
        <form id="payment-form" method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-5">
            @csrf
            <input type="hidden" name="disposition_id" value="{{ $selectedDisposition->id }}">
            <input type="hidden" id="max-payable" value="{{ $selectedDisposition->remaining_payment }}">

            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="wallet" class="w-4 h-4 text-primary-600"></i>
                Langkah 2: Rincian Realisasi Pembayaran
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Tanggal Bayar -->
                <div>
                    <label for="payment_date" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Tanggal Realisasi Pembayaran <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="payment_date" 
                        name="payment_date" 
                        value="{{ old('payment_date', date('Y-m-d')) }}" 
                        required 
                        placeholder="Pilih tanggal pembayaran..."
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white"
                    >
                    @error('payment_date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Nominal Bayar -->
                <div>
                    <label for="amount" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider flex items-center justify-between">
                        <span>Nominal Yang Dibayarkan (Rp) <span class="text-rose-500">*</span></span>
                        <button type="button" onclick="setFullAmount()" class="px-2 py-0.5 rounded-md bg-primary-50 hover:bg-primary-100 text-primary-700 text-[10px] font-bold transition">
                            Bayar Lunas Sisa
                        </button>
                    </label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="1" 
                        max="{{ $selectedDisposition->remaining_payment }}" 
                        id="amount" 
                        name="amount" 
                        value="{{ old('amount', $selectedDisposition->remaining_payment) }}" 
                        required 
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono font-bold text-slate-800"
                    >
                    @error('amount') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Metode Bayar -->
                <div>
                    <label for="method" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Metode Pembayaran <span class="text-rose-500">*</span>
                    </label>
                    <select id="method" name="method" required class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="transfer" {{ old('method') === 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                        <option value="cash" {{ old('method') === 'cash' ? 'selected' : '' }}>Kas Tunai (Petty Cash)</option>
                        <option value="other" {{ old('method') === 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                <!-- Rekening Sumber PT JMM -->
                <div>
                    <label for="source_account_id" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Rekening Sumber (PT JMM)
                    </label>
                    <select id="source_account_id" name="source_account_id" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">-- Kas Tunai / Tidak Ditentukan --</option>
                        @foreach ($sourceAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('source_account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->bank_name }} - {{ $acc->account_number }} (a.n. {{ $acc->owner_name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Penerima Yang Dituju -->
                <div>
                    <label for="recipient_id" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Penerima Pembayaran
                    </label>
                    <select id="recipient_id" name="recipient_id" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">-- Seluruh Penerima Disposisi --</option>
                        @foreach ($selectedDisposition->recipients as $rec)
                            <option value="{{ $rec->id }}" {{ old('recipient_id') == $rec->id ? 'selected' : '' }}>
                                {{ $rec->recipient_name }} &bull; {{ $rec->primary_bank_name }} ({{ $rec->primary_account_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Nomor Referensi / Struk Bank -->
            <div>
                <label for="reference_number" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Nomor Referensi Bank / No. Bukti Transaksi
                </label>
                <input 
                    type="text" 
                    id="reference_number" 
                    name="reference_number" 
                    value="{{ old('reference_number') }}" 
                    placeholder="Contoh: TRF-20261113-098273 atau No. Resi Bank" 
                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono"
                >
            </div>

            <!-- Upload Bukti Transfer -->
            <div>
                <label for="proof_file" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Unggah Bukti Transfer / Nota Struk Kas (PDF, JPG, PNG &bull; Maks 5MB)
                </label>
                <input 
                    type="file" 
                    id="proof_file" 
                    name="proof_file" 
                    accept=".pdf,.png,.jpg,.jpeg" 
                    class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer"
                >
                @error('proof_file') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Catatan Pembayaran -->
            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Catatan Kasir / Finance
                </label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    rows="2" 
                    placeholder="Catatan tambahan realisasi transfer atau keterangan kas..." 
                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >{{ old('notes') }}</textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <a href="{{ route('payments.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                    Batal
                </a>
                <button 
                    type="button" 
                    onclick="confirmPayment()" 
                    class="px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition flex items-center gap-2 shadow-md shadow-primary-600/20"
                >
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan & Terbitkan Voucher Pembayaran</span>
                </button>
            </div>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function setFullAmount() {
        const max = document.getElementById('max-payable').value;
        document.getElementById('amount').value = max;
    }

    function confirmPayment() {
        const amount = parseFloat(document.getElementById('amount').value);
        const max = parseFloat(document.getElementById('max-payable').value);

        if (!amount || amount <= 0) {
            Swal.fire('Nominal Tidak Valid', 'Silakan masukkan nominal pembayaran yang valid.', 'warning');
            return;
        }

        if (amount > max) {
            Swal.fire({
                title: 'Melebihi Sisa Tagihan',
                text: 'Nominal pembayaran tidak boleh melebihi sisa yang belum dibayar.',
                icon: 'error',
                confirmButtonColor: '#e11d48'
            });
            return;
        }

        Swal.fire({
            title: 'Terbitkan Pembayaran?',
            text: `Voucher pembayaran akan dicatat sebesar Rp ${amount.toLocaleString('id-ID', { minimumFractionDigits: 2 })}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Terbitkan Voucher',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#026ec4'
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('payment-form').submit();
            }
        });
    }
</script>
@endpush

