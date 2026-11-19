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
                        <option value="">-- Kas Tunai / Petty Cash (PT JMM) --</option>
                        @foreach ($sourceAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('source_account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->bank_name }} - {{ $acc->account_number }} (a.n. {{ $acc->owner_name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Penerima Yang Dituju -->
                <div>
                    <label for="recipient_select" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Penerima Pembayaran
                    </label>
                    <select id="recipient_select" name="recipient_select" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @if ($selectedDisposition->recipients->count() > 0)
                            <optgroup label="-- Penerima Dokumen Disposisi Ini --">
                                @foreach ($selectedDisposition->recipients as $rec)
                                    <option value="rec_{{ $rec->id }}" {{ (old('recipient_select', 'rec_' . $selectedDisposition->recipients->first()?->id) == 'rec_' . $rec->id || old('recipient_id') == $rec->id) ? 'selected' : '' }}>
                                        {{ $rec->recipient_name }} &bull; {{ $rec->primary_bank_name }} ({{ $rec->primary_account_number }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if (isset($registeredRecipients) && $registeredRecipients->count() > 0)
                            <optgroup label="-- Rekening Penerima Terdaftar (Master Bank) --">
                                @foreach ($registeredRecipients as $acc)
                                    <option value="acc_{{ $acc->id }}" {{ old('recipient_select') == 'acc_' . $acc->id ? 'selected' : '' }}>
                                        {{ $acc->owner_name }} &bull; {{ $acc->bank_name }} ({{ $acc->account_number }}) [{{ $acc->owner_type_label }}]
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
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

            <!-- Upload Bukti Transfer dengan dukungan Ctrl+V (Paste Screenshot Windows) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label for="proof_file" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                        Unggah Bukti Transfer / Nota Struk Kas
                    </label>
                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-700 bg-primary-50 px-2 py-0.5 rounded-md border border-primary-200">
                        <i data-lucide="clipboard" class="w-3.5 h-3.5"></i>
                        <span>Bisa tekan <strong>Ctrl + V</strong> untuk Paste Screenshot</span>
                    </span>
                </div>

                <!-- Dropzone & Paste Box -->
                <div 
                    id="proof-dropzone" 
                    tabindex="0"
                    onclick="document.getElementById('proof_file').click()"
                    class="relative border-2 border-dashed border-slate-300 hover:border-primary-500 rounded-2xl p-5 text-center cursor-pointer bg-slate-50/60 hover:bg-primary-50/20 transition-all focus:outline-none focus:ring-2 focus:ring-primary-500 group"
                >
                    <input 
                        type="file" 
                        id="proof_file" 
                        name="proof_file" 
                        accept=".pdf,.png,.jpg,.jpeg" 
                        class="hidden"
                    >

                    <!-- State 1: Kosong / Belum Ada File -->
                    <div id="proof-empty-state" class="space-y-2 py-1">
                        <div class="w-12 h-12 rounded-full bg-white shadow-xs border border-slate-200 text-slate-400 group-hover:text-primary-600 group-hover:border-primary-300 flex items-center justify-center mx-auto transition-colors">
                            <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-700">
                                Klik untuk pilih file, atau seret & jatuhkan gambar ke sini
                            </p>
                            <p class="text-[11px] text-slate-500 mt-1">
                                Sering screenshot di Windows? Cukup tekan <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-white border border-slate-300 rounded shadow-2xs font-semibold text-slate-700">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-white border border-slate-300 rounded shadow-2xs font-semibold text-slate-700">V</kbd> di mana saja pada layar ini.
                            </p>
                        </div>
                        <span class="inline-block text-[10px] text-slate-400 font-medium">Format didukung: JPG, JPEG, PNG, PDF &bull; Maks. 5 MB</span>
                    </div>

                    <!-- State 2: File Terisi / Screenshot Ditempel (Default Hidden) -->
                    <div id="proof-filled-state" class="hidden flex items-center justify-between p-3 rounded-xl bg-white border border-slate-200 text-left">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Thumbnail Preview -->
                            <div class="w-14 h-14 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 flex-shrink-0 flex items-center justify-center relative">
                                <img id="proof-preview-img" src="" alt="Preview Bukti" class="w-full h-full object-cover hidden">
                                <i id="proof-preview-icon" data-lucide="file-text" class="w-6 h-6 text-slate-400"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span id="proof-source-badge" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Screenshot Ditempel (Ctrl+V)
                                    </span>
                                    <span id="proof-file-size" class="text-[11px] text-slate-400 font-mono">0 KB</span>
                                </div>
                                <p id="proof-file-name" class="text-xs font-bold text-slate-800 truncate mt-1 max-w-xs sm:max-w-md">bukti_transfer.png</p>
                                <p class="text-[10px] text-emerald-600 font-medium flex items-center gap-1 mt-0.5">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i>
                                    <span>File siap diunggah saat formulir disimpan</span>
                                </p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            onclick="event.stopPropagation(); removeProofFile();" 
                            class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-semibold flex items-center gap-1 transition flex-shrink-0"
                            title="Hapus / Ganti File"
                        >
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>

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

    // ==========================================
    // LOGIKA UPLOAD BUKTI & PASTE SCREENSHOT (CTRL+V)
    // ==========================================
    const proofInput = document.getElementById('proof_file');
    const proofDropzone = document.getElementById('proof-dropzone');
    const emptyState = document.getElementById('proof-empty-state');
    const filledState = document.getElementById('proof-filled-state');
    const previewImg = document.getElementById('proof-preview-img');
    const previewIcon = document.getElementById('proof-preview-icon');
    const fileNameEl = document.getElementById('proof-file-name');
    const fileSizeEl = document.getElementById('proof-file-size');
    const sourceBadge = document.getElementById('proof-source-badge');

    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function displayProofPreview(file, isPasted = false) {
        if (!file) return;

        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = formatBytes(file.size);
        sourceBadge.textContent = isPasted ? 'Screenshot Ditempel (Ctrl+V)' : 'File Dipilih';
        sourceBadge.className = isPasted 
            ? 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800'
            : 'px-2 py-0.5 rounded text-[10px] font-bold bg-primary-100 text-primary-800';

        if (file.type && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
                previewIcon.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            previewImg.classList.add('hidden');
            previewIcon.classList.remove('hidden');
        }

        emptyState.classList.add('hidden');
        filledState.classList.remove('hidden');

        if (window.refreshLucideIcons) window.refreshLucideIcons();
    }

    function removeProofFile() {
        if (proofInput) proofInput.value = '';
        if (previewImg) {
            previewImg.src = '';
            previewImg.classList.add('hidden');
        }
        if (previewIcon) previewIcon.classList.remove('hidden');
        if (filledState) filledState.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
    }

    function handleProofFile(file, isPasted = false) {
        if (!file) return;

        // Validasi tipe file
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        const isMatchExt = file.name && file.name.match(/\.(jpg|jpeg|png|pdf)$/i);
        if (!validTypes.includes(file.type) && !isMatchExt) {
            Swal.fire({
                title: 'Format Tidak Sesuai',
                text: 'Harap unggah gambar bukti transfer (JPG, JPEG, PNG) atau dokumen PDF.',
                icon: 'warning',
                confirmButtonColor: '#026ec4'
            });
            return;
        }

        // Validasi ukuran maks 5 MB
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
                title: 'Ukuran Terlalu Besar',
                text: 'Ukuran file bukti transfer maksimal 5 MB.',
                icon: 'warning',
                confirmButtonColor: '#026ec4'
            });
            return;
        }

        // Pasang file ke elemen input[type="file"] via DataTransfer
        try {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            proofInput.files = dataTransfer.files;
        } catch (err) {
            console.error('DataTransfer assignment error:', err);
        }

        displayProofPreview(file, isPasted);

        if (isPasted) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Screenshot Berhasil Ditempel!',
                text: file.name,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    }

    // Listen dialog file explorer
    if (proofInput) {
        proofInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                displayProofPreview(this.files[0], false);
            }
        });
    }

    // Drag and Drop pada box
    if (proofDropzone) {
        ['dragenter', 'dragover'].forEach(name => {
            proofDropzone.addEventListener(name, (e) => {
                e.preventDefault();
                e.stopPropagation();
                proofDropzone.classList.add('border-primary-500', 'bg-primary-50/40');
            }, false);
        });

        ['dragleave', 'drop'].forEach(name => {
            proofDropzone.addEventListener(name, (e) => {
                e.preventDefault();
                e.stopPropagation();
                proofDropzone.classList.remove('border-primary-500', 'bg-primary-50/40');
            }, false);
        });

        proofDropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt?.files;
            if (files && files.length > 0) {
                handleProofFile(files[0], false);
            }
        });
    }

    // GLOBAL CTRL + V (Paste Screenshot Windows dari Clipboard)
    window.addEventListener('paste', function(e) {
        const clipboard = e.clipboardData || window.clipboardData;
        if (!clipboard || !clipboard.items) return;

        const items = clipboard.items;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                if (blob) {
                    e.preventDefault();

                    const now = new Date();
                    const pad = (n) => String(n).padStart(2, '0');
                    const timestamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}_${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
                    const ext = blob.type === 'image/jpeg' ? 'jpg' : 'png';
                    const filename = `screenshot_bukti_${timestamp}.${ext}`;

                    const file = new File([blob], filename, {
                        type: blob.type,
                        lastModified: Date.now()
                    });

                    handleProofFile(file, true);
                    break;
                }
            }
        }
    });
</script>
@endpush

