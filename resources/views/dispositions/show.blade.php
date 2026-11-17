@extends('layouts.app')

@section('title', 'Detail Disposisi: ' . ($disposition->disposition_number ?: 'Draft #' . $disposition->id))

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header & Action Toolbar -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-extrabold text-slate-800">
                    {{ $disposition->disposition_number ?: 'Draft Pengajuan #' . $disposition->id }}
                </h1>
                <!-- Badges -->
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                    @if($disposition->approval_status === 'approved') bg-emerald-100 text-emerald-800
                    @elseif($disposition->approval_status === 'partially_approved') bg-teal-100 text-teal-800
                    @elseif($disposition->approval_status === 'rejected') bg-rose-100 text-rose-800
                    @elseif($disposition->approval_status === 'revision') bg-amber-100 text-amber-800
                    @elseif($disposition->approval_status === 'draft') bg-slate-100 text-slate-700
                    @else bg-blue-100 text-blue-800 @endif">
                    {{ $disposition->approval_status_label }}
                </span>

                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                    @if($disposition->payment_status === 'paid') bg-emerald-100 text-emerald-800
                    @elseif($disposition->payment_status === 'partially_paid') bg-amber-100 text-amber-800
                    @else bg-slate-100 text-slate-700 @endif">
                    {{ $disposition->payment_status_label }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                <span>Tanggal: <strong class="text-slate-700">{{ $disposition->disposition_date ? $disposition->disposition_date->format('d/m/Y') : '-' }}</strong></span>
                <span>&bull;</span>
                <span>Pemohon: <strong class="text-slate-700">{{ $disposition->applicant?->name }} ({{ $disposition->division?->name ?? 'Tanpa Divisi' }})</strong></span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Kembali -->
            <a href="{{ route('dispositions.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Daftar</span>
            </a>

            <!-- Tombol Format Gambar (JPG) untuk WhatsApp ke Owner (Sesuai Kebutuhan) -->
            @if(Route::has('dispositions.image'))
                <a href="{{ route('dispositions.image', $disposition) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm">
                    <i data-lucide="image" class="w-4 h-4"></i>
                    <span>Cetak / Unduh Disposisi (JPG)</span>
                </a>
            @endif

            <!-- Tombol Cetak / PDF -->
            @if(Route::has('dispositions.pdf'))
                <a href="{{ route('dispositions.pdf', $disposition) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-sm">
                    <i data-lucide="file-text" class="w-4 h-4 text-rose-600"></i>
                    <span>PDF</span>
                </a>
            @endif

            <!-- Tombol Edit jika canBeEditedBy (Admin/Finance full access, Pemohon jika draft/revisi) -->
            @if($disposition->canBeEditedBy(auth()->user()))
                <a href="{{ route('dispositions.edit', $disposition) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold transition shadow-sm">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                    <span>Edit Disposisi</span>
                </a>
            @endif

            <!-- Tombol Submit jika draft -->
            @if($disposition->approval_status === 'draft' && $disposition->applicant_id === auth()->id())
                <form method="POST" action="{{ route('dispositions.submit', $disposition) }}" onsubmit="return confirmSubmit(event)">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition shadow-md shadow-primary-600/20">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Kirim ke Approval</span>
                    </button>
                </form>
            @endif

            <!-- Tombol Hapus jika diizinkan (Admin/Finance atau Pemohon jika draft) -->
            @if($disposition->canBeDeletedBy(auth()->user()))
                <form method="POST" action="{{ route('dispositions.destroy', $disposition) }}" onsubmit="return confirmDelete(event)">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold transition">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span>Hapus Disposisi</span>
                    </button>
                </form>
            @endif

            <!-- Tombol Review Approval jika user adalah approver pada step ini -->
            @php
                $user = auth()->user();
                $canReview = $user ? $disposition->canBeApprovedBy($user) : false;
            @endphp

            @if($canReview && Route::has('approvals.review'))
                <a href="{{ route('approvals.review', $disposition) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md shadow-emerald-600/20">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    <span>Review & Putusan Approval</span>
                </a>
            @endif

            <!-- Tombol Catat Pembayaran jika disetujui & ada sisa bayar & user finance/owner -->
            @if(in_array($disposition->approval_status, ['approved', 'partially_approved']) && $disposition->remaining_payment > 0 && ($user->isAdminFinance() || $user->isOwner()) && Route::has('payments.create'))
                <a href="{{ route('payments.create', ['disposition_id' => $disposition->id]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary-700 hover:bg-primary-800 text-white text-xs font-bold transition shadow-md shadow-primary-700/20">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                    <span>Catat Pembayaran</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Banner Peringatan Revisi -->
    @if ($disposition->approval_status === \App\Models\Disposition::STATUS_REVISION)
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5"></i>
            <div class="text-xs space-y-1">
                <p class="font-bold text-sm">Disposisi Memerlukan Revisi</p>
                <p>Pengajuan dikembalikan oleh approver untuk diperbaiki. Silakan klik tombol <strong>"Edit Disposisi"</strong> untuk menyesuaikan data dan mengirimkannya kembali ke alur approval.</p>
            </div>
        </div>
    @endif

    <!-- Banner Penolakan -->
    @if ($disposition->approval_status === \App\Models\Disposition::STATUS_REJECTED)
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-start gap-3">
            <i data-lucide="x-circle" class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5"></i>
            <div class="text-xs space-y-1">
                <p class="font-bold text-sm">Pengajuan Disposisi Ditolak</p>
                <p>Disposisi ini telah ditolak dan tidak dapat diproses lebih lanjut.</p>
            </div>
        </div>
    @endif

    <!-- Kartu Financial Overview (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Pengajuan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Total Pengajuan</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-xl font-extrabold text-slate-800 font-mono">
                Rp {{ number_format((float) $disposition->requested_total, 2, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">{{ $disposition->items->count() }} item pengeluaran</p>
        </div>

        <!-- Disetujui -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Total Disetujui</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-xl font-extrabold text-emerald-600 font-mono">
                Rp {{ number_format((float) $disposition->approved_total, 2, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">
                @if($disposition->approved_total < $disposition->requested_total && $disposition->approved_total > 0)
                    Disetujui sebagian oleh approver
                @else
                    Persetujuan penuh
                @endif
            </p>
        </div>

        <!-- Dibayar -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Total Dibayar</span>
                <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-xl font-extrabold text-primary-700 font-mono">
                Rp {{ number_format((float) $disposition->paid_total, 2, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">{{ $disposition->payments->where('status', 'confirmed')->count() }} transaksi pembayaran</p>
        </div>

        <!-- Sisa Tagihan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Sisa Belum Dibayar</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-xl font-extrabold text-amber-600 font-mono">
                Rp {{ number_format((float) $disposition->remaining_payment, 2, ',', '.') }}
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Status: {{ $disposition->payment_status_label }}</p>
        </div>
    </div>

    <!-- Informasi Detail & Rincian Pengeluaran -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Informasi Pokok (1 col) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 text-primary-600"></i>
                Informasi Pengajuan
            </h2>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Perihal</span>
                    <span class="font-bold text-slate-800 text-sm">{{ $disposition->subject }}</span>
                </div>
                @if ($disposition->invoice_number)
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-semibold">Nomor Invoice</span>
                        <span class="font-bold text-primary-700 font-mono">{{ $disposition->invoice_number }}</span>
                    </div>
                @endif
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Pemohon</span>
                    <span class="font-semibold text-slate-700">{{ $disposition->applicant?->name }}</span>
                    <span class="text-slate-400 text-[11px]">({{ $disposition->applicant?->role?->display_name }})</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Divisi</span>
                    <span class="font-semibold text-slate-700">{{ $disposition->division?->name ?? 'Tanpa Divisi' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Tanggal Diajukan</span>
                    <span class="font-semibold text-slate-700">{{ $disposition->disposition_date ? $disposition->disposition_date->translatedFormat('d F Y') : '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Keterangan / Latar Belakang</span>
                    <div class="p-3 bg-slate-50 rounded-xl text-slate-600 whitespace-pre-line border border-slate-100 mt-1">
                        {{ $disposition->description ?: 'Tidak ada keterangan tambahan.' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Item (2 cols) -->
        @php
            $hasItemInvoice = $disposition->items->contains(fn($i) => !empty(trim((string)$i->invoice_number)));
            $hasQtyUnit = $disposition->items->contains(function($i) {
                $hasQty = !is_null($i->qty) && (float)$i->qty > 0;
                $unit = trim((string)$i->unit);
                $hasUnit = !empty($unit) && $unit !== '-';
                return $hasQty || $hasUnit;
            });
            $labelColspan = 1 + ($hasItemInvoice ? 1 : 0) + 1 + ($hasQtyUnit ? 2 : 0);
        @endphp
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-primary-600"></i>
                    Rincian Item Pengeluaran
                </span>
                <span class="text-[11px] font-semibold text-slate-400">{{ $disposition->items->count() }} item</span>
            </h2>

            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-2.5 pr-2 w-8 text-center">#</th>
                            @if ($hasItemInvoice)
                                <th class="py-2.5 px-2">No. Invoice</th>
                            @endif
                            <th class="py-2.5 px-2">Uraian Kebutuhan</th>
                            @if ($hasQtyUnit)
                                <th class="py-2.5 px-2 text-right">Qty</th>
                                <th class="py-2.5 px-2 text-center">Satuan</th>
                            @endif
                            <th class="py-2.5 px-2 text-right">Subtotal</th>
                            <th class="py-2.5 pl-2 text-center">Nota / Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach ($disposition->items as $idx => $item)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-3 pr-2 text-center text-slate-400 font-semibold">{{ $idx + 1 }}</td>
                                @if ($hasItemInvoice)
                                    <td class="py-3 px-2 font-mono text-[11px] text-slate-600">{{ $item->invoice_number ?: '-' }}</td>
                                @endif
                                <td class="py-3 px-2 font-semibold text-slate-800">{{ $item->description }}</td>
                                @if ($hasQtyUnit)
                                    <td class="py-3 px-2 text-right font-mono">{{ !is_null($item->qty) ? rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') : '-' }}</td>
                                    <td class="py-3 px-2 text-center text-slate-500">{{ $item->unit ?: '-' }}</td>
                                @endif
                                <td class="py-3 px-2 text-right font-mono font-bold text-slate-800">Rp {{ number_format((float) $item->amount, 2, ',', '.') }}</td>
                                <td class="py-3 pl-2 text-center">
                                    @if($item->receipt_path)
                                        <a href="{{ asset('storage/' . $item->receipt_path) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-[11px] border border-emerald-200 transition" title="Lihat Nota: {{ $item->receipt_name }}">
                                            <i data-lucide="receipt" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            <span class="truncate max-w-[110px]">{{ $item->receipt_name ?: 'Buka Nota' }}</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-[11px] italic">Tanpa Nota</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @if ((float) $disposition->ppn > 0)
                            <tr class="border-t border-slate-100 font-bold text-xs bg-slate-50/30">
                                <td colspan="{{ $labelColspan }}" class="py-2 px-2 text-right uppercase tracking-wider text-slate-500 text-[10px]">Subtotal Rincian:</td>
                                <td class="py-2 px-2 text-right font-mono text-slate-700">
                                    Rp {{ number_format($disposition->items->sum('amount'), 2, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                            <tr class="border-t border-slate-100 font-bold text-xs bg-slate-50/30">
                                <td colspan="{{ $labelColspan }}" class="py-2 px-2 text-right uppercase tracking-wider text-slate-500 text-[10px]">PPN:</td>
                                <td class="py-2 px-2 text-right font-mono text-slate-700">
                                    Rp {{ number_format((float) $disposition->ppn, 2, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        @endif
                        <tr class="border-t-2 border-slate-200 font-bold text-xs bg-slate-50/70">
                            <td colspan="{{ $labelColspan }}" class="py-3 px-2 text-right uppercase tracking-wider text-slate-700">Total Pengajuan:</td>
                            <td class="py-3 px-2 text-right font-mono text-primary-700 text-sm font-extrabold">
                                Rp {{ number_format($disposition->requested_total, 2, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Rincian Penerima Pembayaran -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center justify-between">
            <span class="flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-primary-600"></i>
                Rincian Penerima Pembayaran & Rekening
            </span>
            <span class="text-[11px] font-semibold text-slate-400">{{ $disposition->recipients->count() }} Penerima</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($disposition->recipients as $idx => $rec)
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold">
                                {{ $idx + 1 }}
                            </span>
                            <div>
                                <h3 class="text-xs font-bold text-slate-800">{{ $rec->recipient_name }}</h3>
                                <span class="text-[10px] uppercase font-semibold text-slate-400">{{ $rec->recipient_type_label }}</span>
                            </div>
                        </div>
                        <div class="text-right max-w-[200px]">
                            <span class="text-[10px] text-slate-400 block uppercase font-semibold">Keterangan / Cabang</span>
                            <span class="text-xs font-semibold text-slate-700">
                                {{ $rec->notes ?: '-' }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs">
                        <!-- Rekening Utama 1 -->
                        <div class="p-2.5 rounded-lg bg-white border border-slate-200/80">
                            <div class="flex items-center justify-between text-[10px] uppercase font-bold text-slate-400 mb-1">
                                <span class="text-emerald-700 flex items-center gap-1">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i>
                                    Rekening Utama 1
                                </span>
                                <span>{{ $rec->primary_bank_name }}</span>
                            </div>
                            <div class="font-mono font-bold text-slate-800 text-sm tracking-wide">
                                {{ $rec->primary_account_number }}
                            </div>
                            <div class="text-[11px] text-slate-600 mt-0.5">
                                a.n. <strong class="text-slate-800">{{ $rec->primary_account_holder }}</strong>
                            </div>
                        </div>

                        <!-- Rekening Alternatif 2 jika ada -->
                        @if ($rec->secondary_account_number)
                            <div class="p-2.5 rounded-lg bg-white/70 border border-slate-200/60">
                                <div class="flex items-center justify-between text-[10px] uppercase font-bold text-slate-400 mb-1">
                                    <span class="text-slate-500">Rekening Alternatif 2</span>
                                    <span>{{ $rec->secondary_bank_name }}</span>
                                </div>
                                <div class="font-mono text-slate-700 text-xs">
                                    {{ $rec->secondary_account_number }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    a.n. {{ $rec->secondary_account_holder }}
                                </div>
                            </div>
                        @endif

                        @if ($rec->notes)
                            <p class="text-[11px] text-slate-500 italic">
                                Catatan: {{ $rec->notes }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Riwayat Alur Approval & Catatan Putusan -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center gap-2">
            <i data-lucide="git-commit" class="w-4 h-4 text-primary-600"></i>
            Riwayat & Status Alur Persetujuan (Approval Trail)
        </h2>

        <!-- Step Indicator Flow (2 Tahap: Manager -> Finance Arahan Owner) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 py-2">
            <!-- 1. Pemohon / Pengajuan -->
            <div class="p-3 rounded-xl border border-emerald-200 bg-emerald-50/60 text-xs space-y-1">
                <div class="flex items-center justify-between text-emerald-800 font-bold">
                    <span>1. Diajukan oleh (Admin/Finance)</span>
                    <i data-lucide="check" class="w-4 h-4"></i>
                </div>
                <p class="text-[11px] text-emerald-700 font-bold">{{ $disposition->applicant?->name }}</p>
                <p class="text-[10px] text-emerald-600">{{ $disposition->disposition_date ? $disposition->disposition_date->format('d/m/Y') : ($disposition->created_at ? $disposition->created_at->format('d/m/Y') : '-') }}</p>
            </div>

            <!-- 2. Manager -->
            @php
                $mgrApproval = $disposition->approvals->where('stage', 'manager')->first();
            @endphp
            <div class="p-3 rounded-xl border text-xs space-y-1
                @if($mgrApproval && in_array($mgrApproval->decision, ['approved', 'partially_approved'])) border-emerald-200 bg-emerald-50/60 text-emerald-800
                @elseif($mgrApproval && $mgrApproval->decision === 'rejected') border-rose-200 bg-rose-50 text-rose-800
                @elseif($mgrApproval && $mgrApproval->decision === 'revision_requested') border-amber-200 bg-amber-50 text-amber-800
                @elseif($disposition->approval_status === 'waiting_manager') border-blue-300 bg-blue-50/60 text-blue-800
                @else border-slate-200 bg-slate-50 text-slate-400 @endif">
                <div class="flex items-center justify-between font-bold">
                    <span>2. Persetujuan Manager</span>
                    @if($mgrApproval && in_array($mgrApproval->decision, ['approved', 'partially_approved']))
                        <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                    @elseif($disposition->approval_status === 'waiting_manager')
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    @endif
                </div>
                <p class="text-[11px] font-bold">{{ $mgrApproval?->actor?->name ?? ($disposition->approval_status === 'waiting_manager' ? 'Menunggu Persetujuan Manager' : '-') }}</p>
                <p class="text-[10px] text-slate-500">{{ $mgrApproval ? $mgrApproval->created_at->format('d/m/Y H:i') : ($disposition->approval_status === 'waiting_manager' ? 'Dalam Antrean' : '-') }}</p>
            </div>

            <!-- 3. Finance (Arahan Owner) -->
            @php
                $finApproval = $disposition->approvals->where('stage', 'finance')->first();
            @endphp
            <div class="p-3 rounded-xl border text-xs space-y-1
                @if($finApproval && in_array($finApproval->decision, ['approved', 'partially_approved'])) border-emerald-200 bg-emerald-50/60 text-emerald-800
                @elseif($finApproval && $finApproval->decision === 'rejected') border-rose-200 bg-rose-50 text-rose-800
                @elseif($finApproval && $finApproval->decision === 'revision_requested') border-amber-200 bg-amber-50 text-amber-800
                @elseif($disposition->approval_status === 'waiting_finance') border-blue-300 bg-blue-50/60 text-blue-800
                @else border-slate-200 bg-slate-50 text-slate-400 @endif">
                <div class="flex items-center justify-between font-bold">
                    <span>3. Keputusan Akhir Finance (Sesuai Arahan Owner)</span>
                    @if($finApproval && in_array($finApproval->decision, ['approved', 'partially_approved']))
                        <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                    @elseif($disposition->approval_status === 'waiting_finance')
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    @endif
                </div>
                <p class="text-[11px] font-bold">{{ $finApproval?->actor?->name ?? ($disposition->approval_status === 'waiting_finance' ? 'Menunggu Keputusan Finance' : '-') }}</p>
                <p class="text-[10px] text-slate-500">{{ $finApproval ? $finApproval->created_at->format('d/m/Y H:i') : ($disposition->approval_status === 'waiting_finance' ? 'Dalam Antrean' : '-') }}</p>
            </div>
        </div>

        <!-- Tabel Catatan Detail Approval -->
        @if ($disposition->approvals->count() > 0)
            <div class="overflow-x-auto -mx-6 px-6 pt-2">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-2 px-2">Tahap</th>
                            <th class="py-2 px-2">Approver</th>
                            <th class="py-2 px-2">Keputusan</th>
                            <th class="py-2 px-2 text-right">Nominal Disetujui</th>
                            <th class="py-2 px-2">Catatan / Alasan</th>
                            <th class="py-2 px-2 text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach ($disposition->approvals as $appr)
                            <tr>
                                <td class="py-2.5 px-2 font-bold uppercase text-slate-700">{{ $appr->step }}</td>
                                <td class="py-2.5 px-2 font-medium text-slate-800">{{ $appr->actor?->name }} ({{ $appr->actor?->role?->display_name }})</td>
                                <td class="py-2.5 px-2">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold
                                        @if($appr->action === 'approved') bg-emerald-100 text-emerald-800
                                        @elseif($appr->action === 'partially_approved') bg-teal-100 text-teal-800
                                        @elseif($appr->action === 'revision') bg-amber-100 text-amber-800
                                        @elseif($appr->action === 'rejected') bg-rose-100 text-rose-800
                                        @else bg-slate-100 text-slate-700 @endif">
                                        {{ strtoupper(str_replace('_', ' ', $appr->action)) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono font-semibold text-slate-700">
                                    {{ $appr->approved_amount ? 'Rp ' . number_format($appr->approved_amount, 2, ',', '.') : '-' }}
                                </td>
                                <td class="py-2.5 px-2 text-slate-600">{{ $appr->notes ?: '-' }}</td>
                                <td class="py-2.5 px-2 text-right text-[11px] text-slate-400">{{ $appr->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Riwayat Transaksi Pembayaran -->
    @if ($disposition->payments->count() > 0)
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4 h-4 text-primary-600"></i>
                    Riwayat Pembayaran & Bukti Transfer
                </span>
                <span class="text-[11px] font-semibold text-slate-400">{{ $disposition->payments->count() }} Pembayaran</span>
            </h2>

            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-2 px-2">No. Voucher</th>
                            <th class="py-2 px-2">Tanggal Bayar</th>
                            <th class="py-2 px-2">Metode / Rekening Sumber</th>
                            <th class="py-2 px-2 text-right">Nominal Bayar</th>
                            <th class="py-2 px-2">Pencatat</th>
                            <th class="py-2 px-2">Status</th>
                            <th class="py-2 px-2 text-center">Bukti Transfer</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach ($disposition->payments as $pay)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="py-2.5 px-2 font-mono font-bold text-slate-800">{{ $pay->payment_number }}</td>
                                <td class="py-2.5 px-2 text-slate-600">{{ $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-' }}</td>
                                <td class="py-2.5 px-2 text-slate-700">
                                    <span class="font-semibold uppercase">{{ $pay->payment_method }}</span>
                                    @if ($pay->sourceAccount)
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $pay->sourceAccount->bank_name }} - {{ $pay->sourceAccount->account_number }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 px-2 text-right font-mono font-bold text-emerald-700">
                                    Rp {{ number_format($pay->amount, 2, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-2 text-slate-600">{{ $pay->recorder?->name }}</td>
                                <td class="py-2.5 px-2">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold
                                        @if($pay->status === 'confirmed') bg-emerald-100 text-emerald-800
                                        @elseif($pay->status === 'reversed') bg-rose-100 text-rose-800
                                        @else bg-slate-100 text-slate-700 @endif">
                                        {{ strtoupper($pay->status) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    @if ($pay->proof_attachment)
                                        <a href="{{ asset('storage/' . $pay->proof_attachment) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-primary-50 hover:bg-primary-100 text-[11px] text-primary-700 font-semibold transition">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>Lihat Bukti</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Lampiran Dokumen Pendukung -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center justify-between">
            <span class="flex items-center gap-2">
                <i data-lucide="paperclip" class="w-4 h-4 text-primary-600"></i>
                Lampiran & Berkas Pendukung
            </span>
            <span class="text-[11px] font-semibold text-slate-400">{{ $disposition->attachments->count() }} Berkas</span>
        </h2>

        @if ($disposition->attachments->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach ($disposition->attachments as $att)
                    <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="group p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-primary-300 hover:shadow-sm transition flex items-center justify-between gap-3 text-xs cursor-pointer">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <i data-lucide="file" class="w-5 h-5 text-primary-600 flex-shrink-0 group-hover:scale-110 transition-transform"></i>
                            <div class="min-w-0">
                                <span class="font-semibold text-slate-800 group-hover:text-primary-700 truncate block">
                                    {{ $att->file_name }}
                                </span>
                                <p class="text-[10px] text-slate-400">{{ number_format($att->file_size / 1024, 0) }} KB &bull; {{ $att->uploader?->name }}</p>
                            </div>
                        </div>
                        <span class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-400 group-hover:text-primary-600 flex-shrink-0 shadow-xs" title="Buka Berkas">
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-xs text-slate-400 italic">Tidak ada lampiran dokumen pendukung untuk pengajuan ini.</p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmSubmit(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Kirim Pengajuan?',
            text: 'Disposisi akan diteruskan ke antrean approval Manager.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Kirim',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#026ec4'
        }).then(result => {
            if (result.isConfirmed) {
                e.target.submit();
            }
        });
        return false;
    }

    function confirmDelete(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Hapus Draft?',
            text: 'Draft disposisi ini akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#e11d48'
        }).then(result => {
            if (result.isConfirmed) {
                e.target.submit();
            }
        });
        return false;
    }
</script>
@endpush

