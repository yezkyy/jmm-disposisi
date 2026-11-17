@extends('layouts.app')

@section('title', 'Telaah Persetujuan: ' . ($disposition->disposition_number ?: 'Draft #' . $disposition->id))

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold uppercase bg-primary-100 text-primary-800 tracking-wider">
                    Tahap: {{ $stageName }}
                </span>
                <h1 class="text-xl font-extrabold text-slate-800">
                    {{ $disposition->disposition_number ?: 'Disposisi #' . $disposition->id }}
                </h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Diajukan oleh <strong class="text-slate-700">{{ $disposition->applicant?->name }}</strong> ({{ $disposition->division?->name ?? 'Tanpa Divisi' }}) pada {{ $disposition->created_at->format('d/m/Y H:i') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('approvals.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Inbox</span>
            </a>
            @if(Route::has('dispositions.pdf'))
                <a href="{{ route('dispositions.pdf', $disposition) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-sm">
                    <i data-lucide="file-text" class="w-4 h-4 text-rose-600"></i>
                    <span>Cetak PDF</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Ringkasan Pokok & Nominal -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Perihal Pengeluaran</h2>
            <p class="text-base font-bold text-slate-800">{{ $disposition->subject }}</p>
            <div class="pt-2 border-t border-slate-100">
                <span class="text-[11px] font-semibold text-slate-400 uppercase block mb-1">Latar Belakang / Keterangan:</span>
                <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{{ $disposition->description ?: 'Tidak ada keterangan tambahan.' }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Total Nominal Pengajuan</span>
                <div class="text-2xl font-extrabold text-primary-700 font-mono">
                    Rp {{ number_format($disposition->requested_total, 2, ',', '.') }}
                </div>
            </div>

            @if ($disposition->manager_approved_total && $disposition->manager_approved_total < $disposition->requested_total)
                <div class="p-3 rounded-xl bg-teal-50 border border-teal-200 text-xs">
                    <span class="font-bold text-teal-800 block">Persetujuan Manager:</span>
                    <span class="font-mono font-bold text-teal-900 text-sm">
                        Rp {{ number_format($disposition->manager_approved_total, 2, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-teal-700 block mt-0.5">(Disetujui sebagian pada tahap 1)</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Rincian Biaya Items -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center justify-between">
            <span class="flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-primary-600"></i>
                Rincian Item Biaya
            </span>
            <span class="text-[11px] text-slate-400 font-semibold">{{ $disposition->items->count() }} item</span>
        </h2>

        <div class="overflow-x-auto -mx-6 px-6">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="py-2 px-2 w-8 text-center">#</th>
                        <th class="py-2 px-2">Deskripsi Item</th>
                        <th class="py-2 px-2">Kategori</th>
                        <th class="py-2 px-2 text-right">Qty</th>
                        <th class="py-2 px-2">Satuan</th>
                        <th class="py-2 px-2 text-right">Harga Satuan</th>
                        <th class="py-2 px-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach ($disposition->items as $idx => $item)
                        <tr>
                            <td class="py-2.5 px-2 text-center text-slate-400 font-semibold">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-2 font-medium text-slate-800">{{ $item->item_name }}</td>
                            <td class="py-2.5 px-2">
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $item->category?->name ?? 'Umum' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2 text-right font-mono">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                            <td class="py-2.5 px-2 text-slate-500">{{ $item->unit }}</td>
                            <td class="py-2.5 px-2 text-right font-mono text-slate-600">Rp {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                            <td class="py-2.5 px-2 text-right font-mono font-bold text-slate-800">Rp {{ number_format($item->amount, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rincian Penerima Pembayaran -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center justify-between">
            <span class="flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-primary-600"></i>
                Rincian Penerima Pembayaran & Nomor Rekening
            </span>
            <span class="text-[11px] text-slate-400 font-semibold">{{ $disposition->recipients->count() }} Penerima</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($disposition->recipients as $idx => $rec)
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <div>
                            <h3 class="text-xs font-bold text-slate-800">{{ $rec->recipient_name }}</h3>
                            <span class="text-[10px] font-semibold uppercase text-slate-400">{{ $rec->recipient_type_label }}</span>
                        </div>
                        <span class="text-xs font-extrabold text-primary-700 font-mono">
                            Rp {{ number_format($rec->amount, 2, ',', '.') }}
                        </span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-white border border-slate-200/80 text-xs">
                        <div class="text-[10px] uppercase font-bold text-emerald-700 mb-0.5">Rekening 1 (Utama):</div>
                        <div class="font-mono font-bold text-slate-800">{{ $rec->primary_bank_name }} - {{ $rec->primary_account_number }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">a.n. {{ $rec->primary_account_holder }}</div>
                    </div>

                    @if ($rec->secondary_account_number)
                        <div class="p-2.5 rounded-lg bg-white/70 border border-slate-200/60 text-xs">
                            <div class="text-[10px] uppercase font-bold text-slate-400 mb-0.5">Rekening 2 (Alternatif):</div>
                            <div class="font-mono text-slate-700">{{ $rec->secondary_bank_name }} - {{ $rec->secondary_account_number }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">a.n. {{ $rec->secondary_account_holder }}</div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Berkas Lampiran -->
    @if ($disposition->attachments->count() > 0)
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="paperclip" class="w-4 h-4 text-primary-600"></i>
                Lampiran Pendukung
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach ($disposition->attachments as $att)
                    <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="group p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-primary-300 hover:shadow-sm transition flex items-center justify-between gap-3 text-xs cursor-pointer">
                        <div class="flex items-center gap-2 min-w-0">
                            <i data-lucide="file" class="w-4 h-4 text-primary-600 flex-shrink-0 group-hover:scale-110 transition-transform"></i>
                            <span class="font-semibold text-slate-800 group-hover:text-primary-700 truncate block">
                                {{ $att->file_name }}
                            </span>
                        </div>
                        <span class="p-1 rounded bg-white text-slate-400 group-hover:text-primary-600 shadow-xs" title="Buka Berkas">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- FORM KEPUTUSAN APPROVAL (THE DECISION PANEL) -->
    <div class="bg-white rounded-2xl p-6 border-2 border-primary-500/40 shadow-lg space-y-6">
        <div>
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="shield-check" class="w-5 h-5 text-primary-600"></i>
                Keputusan Persetujuan: {{ $stageName }}
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                @if($disposition->approval_status === \App\Models\Disposition::STATUS_WAITING_FINANCE)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 font-semibold border border-amber-200">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-amber-600"></i>
                        Penentuan status akhir ACC / TIDAK ACC dilakukan oleh Finance berdasarkan arahan / informasi langsung dari Owner.
                    </span>
                @else
                    Pilih putusan Anda terhadap pengajuan ini. Setiap keputusan akan tercatat dalam riwayat audit trail.
                @endif
            </p>
        </div>

        <form id="approval-form" method="POST" action="{{ route('approvals.decide', $disposition) }}" class="space-y-5">
            @csrf

            <!-- Radio Pilihan Keputusan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- 1. Setujui Penuh (ACC) -->
                <label class="decision-card relative flex flex-col p-4 rounded-xl border-2 border-slate-200 cursor-pointer hover:border-emerald-500 transition select-none bg-white">
                    <input type="radio" name="decision" value="approved" class="sr-only" checked onchange="handleDecisionChange(this)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </div>
                        <span class="radio-indicator w-4 h-4 rounded-full border-2 border-emerald-600 flex items-center justify-center">
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        </span>
                    </div>
                    <span class="text-xs font-bold text-slate-800">
                        @if($disposition->approval_status === \App\Models\Disposition::STATUS_WAITING_FINANCE)
                            ACC Disposisi (Sesuai Arahan Owner)
                        @else
                            ACC Disposisi (Setujui)
                        @endif
                    </span>
                    <span class="text-[10px] text-slate-500 mt-0.5">
                        @if($disposition->approval_status === \App\Models\Disposition::STATUS_WAITING_FINANCE)
                            Tetapkan status akhir ACC untuk realisasi kas.
                        @else
                            Teruskan pengajuan ke tahap Finance.
                        @endif
                    </span>
                </label>

                <!-- 2. Setujui Sebagian -->
                <label class="decision-card relative flex flex-col p-4 rounded-xl border-2 border-slate-200 cursor-pointer hover:border-teal-500 transition select-none bg-white">
                    <input type="radio" name="decision" value="partially_approved" class="sr-only" onchange="handleDecisionChange(this)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center">
                            <i data-lucide="check-check" class="w-4 h-4"></i>
                        </div>
                        <span class="radio-indicator w-4 h-4 rounded-full border-2 border-slate-300 flex items-center justify-center"></span>
                    </div>
                    <span class="text-xs font-bold text-slate-800">ACC Sebagian</span>
                    <span class="text-[10px] text-slate-500 mt-0.5">Sesuaikan nominal yang disetujui.</span>
                </label>

                <!-- 3. Minta Revisi -->
                <label class="decision-card relative flex flex-col p-4 rounded-xl border-2 border-slate-200 cursor-pointer hover:border-amber-500 transition select-none bg-white">
                    <input type="radio" name="decision" value="revision_requested" class="sr-only" onchange="handleDecisionChange(this)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        </div>
                        <span class="radio-indicator w-4 h-4 rounded-full border-2 border-slate-300 flex items-center justify-center"></span>
                    </div>
                    <span class="text-xs font-bold text-slate-800">Minta Revisi</span>
                    <span class="text-[10px] text-slate-500 mt-0.5">Kembalikan ke pemohon untuk diperbaiki.</span>
                </label>

                <!-- 4. Tolak Pengajuan (TIDAK ACC) -->
                <label class="decision-card relative flex flex-col p-4 rounded-xl border-2 border-slate-200 cursor-pointer hover:border-rose-500 transition select-none bg-white">
                    <input type="radio" name="decision" value="rejected" class="sr-only" onchange="handleDecisionChange(this)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </div>
                        <span class="radio-indicator w-4 h-4 rounded-full border-2 border-slate-300 flex items-center justify-center"></span>
                    </div>
                    <span class="text-xs font-bold text-slate-800">Tolak (TIDAK ACC)</span>
                    <span class="text-[10px] text-slate-500 mt-0.5">Tolak pengajuan disposisi.</span>
                </label>
            </div>

            <!-- Input Tambahan jika Setujui Sebagian -->
            <div id="partial-amount-group" class="hidden p-4 rounded-xl bg-teal-50 border border-teal-200 space-y-2">
                <label for="approved_amount" class="block text-xs font-bold text-teal-900 uppercase tracking-wider">
                    Nominal Yang Disetujui (Rp) <span class="text-rose-500">*</span>
                </label>
                <div class="max-w-xs">
                    <input 
                        type="number" 
                        step="0.01" 
                        min="1" 
                        max="{{ $disposition->requested_total }}" 
                        id="approved_amount" 
                        name="approved_amount" 
                        value="{{ old('approved_amount', $disposition->requested_total) }}" 
                        placeholder="Contoh: 5000000" 
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-teal-300 focus:outline-none focus:ring-2 focus:ring-teal-500 font-mono font-bold bg-white text-teal-900"
                    >
                </div>
                <p class="text-[11px] text-teal-700">Maksimal: Rp {{ number_format($disposition->requested_total, 2, ',', '.') }}</p>
            </div>

            <!-- Catatan / Alasan Putusan -->
            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Catatan Putusan / Alasan <span id="notes-required-indicator" class="text-rose-500 hidden">*</span>
                </label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    rows="3" 
                    placeholder="Tuliskan catatan persetujuan, instruksi pemotongan, arahan revisi, atau alasan penolakan..." 
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >{{ old('notes') }}</textarea>
                @error('notes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Tombol Submit Putusan -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('approvals.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                    Batal
                </a>
                <button 
                    type="button" 
                    onclick="confirmDecision()" 
                    class="px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition flex items-center gap-2 shadow-md shadow-primary-600/20"
                >
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Kirim Putusan Approval</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleDecisionChange(radio) {
        // Reset card styling
        document.querySelectorAll('.decision-card').forEach(card => {
            card.classList.remove('border-emerald-600', 'border-teal-600', 'border-amber-600', 'border-rose-600', 'bg-slate-50');
            const ind = card.querySelector('.radio-indicator');
            ind.innerHTML = '';
            ind.classList.remove('border-emerald-600', 'border-teal-600', 'border-amber-600', 'border-rose-600');
            ind.classList.add('border-slate-300');
        });

        const activeCard = radio.closest('.decision-card');
        const activeInd = activeCard.querySelector('.radio-indicator');
        const val = radio.value;

        const partialGroup = document.getElementById('partial-amount-group');
        const notesReq = document.getElementById('notes-required-indicator');

        if (val === 'approved') {
            activeCard.classList.add('border-emerald-600', 'bg-emerald-50/20');
            activeInd.classList.add('border-emerald-600');
            activeInd.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-600"></span>';
            partialGroup.classList.add('hidden');
            notesReq.classList.add('hidden');
        } else if (val === 'partially_approved') {
            activeCard.classList.add('border-teal-600', 'bg-teal-50/20');
            activeInd.classList.add('border-teal-600');
            activeInd.innerHTML = '<span class="w-2 h-2 rounded-full bg-teal-600"></span>';
            partialGroup.classList.remove('hidden');
            notesReq.classList.remove('hidden');
        } else if (val === 'revision_requested') {
            activeCard.classList.add('border-amber-600', 'bg-amber-50/20');
            activeInd.classList.add('border-amber-600');
            activeInd.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-600"></span>';
            partialGroup.classList.add('hidden');
            notesReq.classList.remove('hidden');
        } else if (val === 'rejected') {
            activeCard.classList.add('border-rose-600', 'bg-rose-50/20');
            activeInd.classList.add('border-rose-600');
            activeInd.innerHTML = '<span class="w-2 h-2 rounded-full bg-rose-600"></span>';
            partialGroup.classList.add('hidden');
            notesReq.classList.remove('hidden');
        }
    }

    function confirmDecision() {
        const decisionInput = document.querySelector('input[name="decision"]:checked');
        if (!decisionInput) {
            Swal.fire('Peringatan', 'Silakan pilih keputusan terlebih dahulu.', 'warning');
            return;
        }

        const decision = decisionInput.value;
        const notes = document.getElementById('notes').value.trim();

        if (decision === 'partially_approved') {
            const amount = parseFloat(document.getElementById('approved_amount').value);
            if (!amount || amount <= 0) {
                Swal.fire('Nominal Tidak Valid', 'Silakan masukkan nominal yang disetujui.', 'warning');
                return;
            }
            if (!notes) {
                Swal.fire('Catatan Wajib', 'Silakan isi catatan untuk persetujuan sebagian.', 'warning');
                return;
            }
        }

        if ((decision === 'revision_requested' || decision === 'rejected') && !notes) {
            Swal.fire('Alasan Wajib', 'Silakan tuliskan alasan permintaan revisi atau penolakan.', 'warning');
            return;
        }

        const titles = {
            'approved': 'Setujui Penuh Pengajuan?',
            'partially_approved': 'Setujui Sebagian Nominal?',
            'revision_requested': 'Minta Revisi Disposisi?',
            'rejected': 'Tolak Pengajuan Disposisi?'
        };

        const texts = {
            'approved': 'Disposisi akan diteruskan ke tahap approval berikutnya.',
            'partially_approved': 'Nominal yang disetujui akan diperbarui sesuai input Anda.',
            'revision_requested': 'Pengajuan akan dikembalikan ke pemohon untuk diperbaiki.',
            'rejected': 'Pengajuan akan ditolak permanen.'
        };

        const confirmColors = {
            'approved': '#059669',
            'partially_approved': '#0d9488',
            'revision_requested': '#d97706',
            'rejected': '#e11d48'
        };

        Swal.fire({
            title: titles[decision],
            text: texts[decision],
            icon: decision === 'rejected' ? 'error' : (decision === 'revision_requested' ? 'warning' : 'question'),
            showCancelButton: true,
            confirmButtonText: 'Ya, Kirim Putusan',
            cancelButtonText: 'Batal',
            confirmButtonColor: confirmColors[decision]
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('approval-form').submit();
            }
        });
    }
</script>
@endpush

