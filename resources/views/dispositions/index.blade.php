@extends('layouts.app')

@section('title', 'Daftar Disposisi Pengeluaran')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="file-text" class="w-6 h-6 text-primary-600"></i>
                Daftar Disposisi Pengeluaran
            </h1>
            <p class="text-xs text-slate-500 mt-1">Kelola dan pantau seluruh riwayat pengajuan, persetujuan, dan pencairan disposisi PT Jaya Mukti Mandiri.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Tombol Download Laporan Excel & PDF (Revisi Versi 3 Poin 6) -->
            <button type="button" onclick="openDownloadReportModal()" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-sm transition">
                <i data-lucide="download" class="w-4 h-4 text-primary-600"></i>
                <span>Download Laporan</span>
            </button>

            <!-- Tombol Bukti Transfer Harian (Revisi Versi 3 Poin 8) -->
            <button type="button" onclick="openDailyProofModal()" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 shadow-sm transition">
                <i data-lucide="image" class="w-4 h-4 text-emerald-600"></i>
                <span>Bukti Transfer Harian</span>
                @if ($dailyTransferProofs->count() > 0)
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">{{ $dailyTransferProofs->count() }}</span>
                @endif
            </button>

            <!-- Tombol Buat Baru -->
            <a href="{{ route('dispositions.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-xs shadow-sm hover:shadow transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Buat Pengajuan Baru</span>
            </a>
        </div>
    </div>

    <!-- Widget Metrik Disposisi Harian (Revisi Versi 3 Poin 7) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- 1. Jumlah Disposisi Hari Ini -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Disposisi Diajukan Hari Ini</span>
                <div class="text-2xl font-black text-slate-800 font-mono">
                    {{ $todayCount }} <span class="text-xs font-semibold text-slate-500 font-sans">Pengajuan</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="file-text" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 2. Total Nominal Pengajuan Hari Ini -->
        <div class="bg-white rounded-2xl p-5 border border-blue-200/80 bg-gradient-to-br from-blue-50/50 to-white shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-600 block mb-1">Total Nominal Hari Ini</span>
                <div class="text-2xl font-black text-blue-900 font-mono">
                    Rp {{ number_format($todayNominal, 0, ',', '.') }}
                </div>
                <p class="text-[10px] text-blue-500 mt-0.5">Total nominal diajukan hari ini</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- 3. Total Disetujui Hari Ini -->
        <div class="bg-white rounded-2xl p-5 border border-emerald-200/80 bg-gradient-to-br from-emerald-50/50 to-white shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 block mb-1">Disetujui Hari Ini</span>
                <div class="text-2xl font-black text-emerald-900 font-mono">
                    Rp {{ number_format($todayApproved, 0, ',', '.') }}
                </div>
                <p class="text-[10px] text-emerald-500 mt-0.5">Disposisi yang berstatus ACC hari ini</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('dispositions.index') }}" x-data="liveFilter({ baseUrl: '{{ route('dispositions.index') }}', targetId: 'dispositions-table-card' })" @submit.prevent="submitFilter()" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search Keyword -->
                <div>
                    <label for="search" class="block text-xs font-semibold text-slate-600 mb-1">Cari Nomor / Perihal / Penerima</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Kata kunci pencarian..."
                            @input.debounce.350ms="submitFilter()"
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>

                <!-- Status Approval -->
                <div>
                    <label for="approval_status" class="block text-xs font-semibold text-slate-600 mb-1">Status Persetujuan</label>
                    <select id="approval_status" name="approval_status" @change="submitFilter()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                        <option value="">Semua Status Approval</option>
                        <option value="draft" {{ request('approval_status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="waiting_manager" {{ request('approval_status') === 'waiting_manager' ? 'selected' : '' }}>Menunggu Manager</option>
                        <option value="waiting_finance" {{ request('approval_status') === 'waiting_finance' ? 'selected' : '' }}>Menunggu Keputusan Finance (Arahan Owner)</option>
                        <option value="revision_requested" {{ request('approval_status') === 'revision_requested' ? 'selected' : '' }}>Perlu Revisi</option>
                        <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Disetujui (ACC Final)</option>
                        <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                <!-- Status Pembayaran -->
                <div>
                    <label for="payment_status" class="block text-xs font-semibold text-slate-600 mb-1">Status Pembayaran</label>
                    <select id="payment_status" name="payment_status" @change="submitFilter()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                        <option value="">Semua Status Pembayaran</option>
                        <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                        <option value="partially_paid" {{ request('payment_status') === 'partially_paid' ? 'selected' : '' }}>Dibayar Sebagian</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Lunas</option>
                    </select>
                </div>

                <!-- Divisi -->
                <div>
                    <label for="division_id" class="block text-xs font-semibold text-slate-600 mb-1">Divisi</label>
                    <select id="division_id" name="division_id" @change="submitFilter()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                        <option value="">Semua Divisi</option>
                        @foreach ($divisions as $div)
                        <option value="{{ $div->id }}" {{ request('division_id') == $div->id ? 'selected' : '' }}>
                            {{ $div->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Date Range & Action Buttons -->
            <div class="flex flex-col sm:flex-row items-end justify-between gap-4 pt-2 border-t border-slate-100">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <div>
                        <label for="start_date" class="block text-[11px] font-semibold text-slate-500 mb-1">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" @change="submitFilter()" placeholder="Pilih mulai..." class="w-36 sm:w-40 px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                    </div>
                    <span class="text-slate-400 mt-5">&mdash;</span>
                    <div>
                        <label for="end_date" class="block text-[11px] font-semibold text-slate-500 mb-1">Tanggal Akhir</label>
                        <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" @change="submitFilter()" placeholder="Pilih akhir..." class="w-36 sm:w-40 px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilter()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium text-xs rounded-xl transition flex items-center gap-1.5" title="Reset Filter">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        <span>Reset Filter</span>
                    </button>
                    <span x-show="loading" class="text-xs font-semibold text-primary-600 flex items-center gap-1.5">
                        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
                        <span>Memfilter...</span>
                    </span>
                </div>
            </div>
        </form>
    </div>

    <!-- Dispositions Table -->
    <div id="dispositions-table-card" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider text-[11px] font-semibold">
                        <th class="py-3.5 px-4">No. Disposisi</th>
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-4">Perihal & Pemohon</th>
                        <th class="py-3.5 px-4 text-right">Nominal Diajukan</th>
                        <th class="py-3.5 px-4 text-right">Nominal Disetujui</th>
                        <th class="py-3.5 px-4 text-center">Status Approval</th>
                        <th class="py-3.5 px-4 text-center">Status Bayar</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($dispositions as $disp)
                    <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="window.location='{{ route('dispositions.show', $disp) }}'">
                        <td class="py-3.5 px-4 font-mono font-bold text-primary-700">
                            @if($disp->disposition_number)
                            <span class="hover:text-primary-800">
                                {{ $disp->disposition_number }}
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                Draft #{{ $disp->id }}
                            </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                            {{ $disp->disposition_date->translatedFormat('d M Y') }}
                        </td>
                        <td class="py-3.5 px-4 max-w-xs">
                            <span class="font-semibold text-slate-900 hover:text-primary-600 truncate block">
                                {{ $disp->subject }}
                            </span>
                            <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                                <span>{{ $disp->applicant?->name ?? 'Pemohon' }}</span>
                                <span>&bull;</span>
                                <span>{{ $disp->division?->name ?? 'Tanpa Divisi' }}</span>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-right font-medium font-mono text-slate-900 whitespace-nowrap">
                            Rp {{ number_format($disp->requested_total, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-medium font-mono whitespace-nowrap">
                            @if($disp->final_approved_total !== null)
                            <span class="text-emerald-700 font-bold">
                                Rp {{ number_format($disp->final_approved_total, 0, ',', '.') }}
                            </span>
                            @else
                            <span class="text-slate-400 italic text-[11px]">-</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $disp->approval_status_badge }}">
                                {{ $disp->approval_status_label }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $disp->payment_status_badge }}">
                                {{ $disp->payment_status_label }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap" onclick="event.stopPropagation()">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('dispositions.show', $disp) }}" class="p-1.5 rounded-lg text-slate-600 hover:text-primary-600 hover:bg-slate-100 transition" title="Lihat Detail">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>

                                <a href="{{ route('dispositions.image', $disp) }}" class="p-1.5 rounded-lg text-slate-600 hover:text-blue-600 hover:bg-slate-100 transition" title="Format Gambar JPG (WhatsApp)">
                                    <i data-lucide="image" class="w-4 h-4"></i>
                                </a>

                                @if($disp->canBeEditedBy(auth()->user()))
                                <a href="{{ route('dispositions.edit', $disp) }}" class="p-1.5 rounded-lg text-slate-600 hover:text-amber-600 hover:bg-slate-100 transition" title="Edit Pengajuan">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </a>
                                @endif

                                @if($disp->canBeDeletedBy(auth()->user()))
                                <form method="POST" action="{{ route('dispositions.destroy', $disp) }}" class="inline" onsubmit="return confirm('Hapus pengajuan disposisi ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus Disposisi">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                <p class="font-medium text-slate-500">Belum ada pengajuan disposisi yang tercatat.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($dispositions->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $dispositions->links() }}
        </div>
        @endif
    </div>

    <!-- ======================================================== -->
    <!-- MODAL DOWNLOAD LAPORAN EXCEL & PDF (Revisi Versi 3 Poin 6) -->
    <!-- ======================================================== -->
    <div id="download-report-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-primary-600 flex items-center justify-center">
                        <i data-lucide="download" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Download Laporan Disposisi</h3>
                        <p class="text-[11px] text-slate-500">Pilih rentang tanggal dan format dokumen yang diinginkan.</p>
                    </div>
                </div>
                <button type="button" onclick="closeDownloadReportModal()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form id="download-report-form" method="GET" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="export-start-date" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Tanggal Mulai <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="export-start-date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-slate-50/50">
                    </div>
                    <div>
                        <label for="export-end-date" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Tanggal Akhir <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="export-end-date" name="end_date" value="{{ request('end_date', now()->toDateString()) }}" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-slate-50/50">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="export-status" class="block text-[11px] font-semibold text-slate-700 mb-1">Status Persetujuan</label>
                        <select id="export-status" name="approval_status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                            <option value="">Semua Status</option>
                            <option value="approved">Disetujui (ACC Final)</option>
                            <option value="waiting_manager">Menunggu Manager</option>
                            <option value="waiting_finance">Menunggu Finance</option>
                            <option value="draft">Draft</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </div>
                    <div>
                        <label for="export-payment" class="block text-[11px] font-semibold text-slate-700 mb-1">Status Pembayaran</label>
                        <select id="export-payment" name="payment_status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                            <option value="">Semua Status</option>
                            <option value="paid">Lunas</option>
                            <option value="partially_paid">Dibayar Sebagian</option>
                            <option value="unpaid">Belum Dibayar</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeDownloadReportModal()" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Batal
                    </button>
                    <!-- Download Excel -->
                    <button type="button" onclick="submitReportDownload('{{ route('reports.export-excel') }}')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                        <span>Unduh Excel (.xlsx)</span>
                    </button>
                    <!-- Download PDF -->
                    <button type="button" onclick="submitReportDownload('{{ route('reports.export-pdf') }}')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>Unduh PDF (.pdf)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL BUKTI TRANSFER HARIAN (Revisi Versi 3 Poin 8)       -->
    <!-- ======================================================== -->
    <div id="daily-proof-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
            <!-- Modal Header -->
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="image" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Bukti Transfer Harian (Gabungan Pembayaran)</h3>
                        <p class="text-[11px] text-slate-500">Unggah satu gambar bukti transfer gabungan berdasarkan tanggal transaksi.</p>
                    </div>
                </div>
                <button type="button" onclick="closeDailyProofModal()" class="text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Modal Content (Scrollable) -->
            <div class="p-6 overflow-y-auto space-y-6">
                <!-- Form Upload Bukti Transfer Baru -->
                <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/40 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                        <i data-lucide="upload-cloud" class="w-4 h-4 text-emerald-600"></i>
                        Unggah Bukti Transfer Baru
                    </h4>

                    <form action="{{ route('daily-transfer-proofs.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                    Tanggal Transaksi <span class="text-rose-500">*</span>
                                </label>
                                <input type="date" name="transfer_date" value="{{ now()->toDateString() }}" required class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                    Keterangan / Judul Singkat <span class="text-slate-400 font-normal">(opsional)</span>
                                </label>
                                <input type="text" name="title" placeholder="Contoh: Gabungan transfer operasional supir & solar" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                File Bukti Transfer (JPG / JPEG / PNG) <span class="text-rose-500">*</span>
                            </label>
                            <input type="file" name="file" accept=".jpg,.jpeg,.png" required class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer border border-slate-200 rounded-xl bg-white">
                            <span class="text-[10px] text-slate-400 mt-1 block">Format: JPG, JPEG, PNG &bull; Maks. 10 MB per gambar</span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea name="notes" rows="2" placeholder="Uraikan nomor referensi mutasi bank atau rincian disposisi yang tercakup..." class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Simpan Bukti Transfer</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Arsip Riwayat Bukti Transfer Harian -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                            <i data-lucide="archive" class="w-4 h-4 text-primary-600"></i>
                            Arsip Bukti Pembayaran Harian
                        </h4>
                        <span class="text-[11px] font-semibold text-slate-400">{{ $dailyTransferProofs->count() }} Gambar Tersimpan</span>
                    </div>

                    @if ($dailyTransferProofs->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            @foreach ($dailyTransferProofs as $proof)
                                <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-xs transition space-y-2.5 flex flex-col justify-between">
                                    <div class="flex items-start gap-3">
                                        <!-- Thumbnail Gambar -->
                                        <div class="w-16 h-16 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 flex-shrink-0 cursor-pointer group relative" onclick="openImagePreview('{{ $proof->file_url }}', '{{ addslashes($proof->title) }}')">
                                            <img src="{{ $proof->file_url }}" alt="Bukti Transfer" class="w-full h-full object-cover group-hover:scale-110 transition-transform">
                                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                <i data-lucide="zoom-in" class="w-4 h-4"></i>
                                            </div>
                                        </div>

                                        <!-- Informasi Bukti -->
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">
                                                    {{ $proof->transfer_date->translatedFormat('d M Y') }}
                                                </span>
                                                <span class="text-[10px] text-slate-400">{{ $proof->formatted_file_size }}</span>
                                            </div>
                                            <h5 class="font-bold text-slate-800 text-xs truncate mt-1" title="{{ $proof->title }}">{{ $proof->title }}</h5>
                                            @if ($proof->notes)
                                                <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ $proof->notes }}</p>
                                            @endif
                                            <p class="text-[10px] text-slate-400 mt-1">Oleh: {{ $proof->uploader?->name ?? 'Admin' }}</p>
                                        </div>
                                    </div>

                                    <!-- Tombol Tindakan -->
                                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                                        <button type="button" onclick="openImagePreview('{{ $proof->file_url }}', '{{ addslashes($proof->title) }}')" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-600 hover:text-primary-800">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>Lihat Penuh</span>
                                        </button>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('daily-transfer-proofs.download', $proof) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 hover:text-slate-800" title="Unduh File Asli">
                                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                <span>Unduh</span>
                                            </a>
                                            @if (auth()->user()->isAdminFinance() || auth()->user()->isOwner())
                                                <form action="{{ route('daily-transfer-proofs.destroy', $proof) }}" method="POST" onsubmit="return confirm('Hapus bukti transfer ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-rose-500 hover:text-rose-700 p-0.5" title="Hapus Bukti Transfer">
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-xl">
                            <i data-lucide="image-off" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-xs font-semibold text-slate-600">Belum ada bukti transfer harian yang diunggah.</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Unggah bukti transfer melalui formulir di atas saat transaksi harian telah selesai.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL LIGHTBOX PREVIEW GAMBAR PENUH                      -->
    <!-- ======================================================== -->
    <div id="image-lightbox-modal" class="fixed inset-0 z-50 bg-slate-900/90 backdrop-blur-md flex items-center justify-center p-4 hidden" onclick="closeImagePreview()">
        <div class="max-w-4xl w-full max-h-[92vh] flex flex-col items-center justify-center space-y-3" onclick="event.stopPropagation()">
            <div class="w-full flex items-center justify-between text-white px-2">
                <span id="lightbox-title" class="font-bold text-xs truncate"></span>
                <div class="flex items-center gap-3">
                    <a id="lightbox-download-btn" href="#" target="_blank" class="text-xs font-semibold hover:text-emerald-400 inline-flex items-center gap-1">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                        <span>Buka Tab Baru</span>
                    </a>
                    <button type="button" onclick="closeImagePreview()" class="text-white/80 hover:text-white p-1">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            <div class="bg-black/40 rounded-xl p-2 border border-white/10 max-h-[82vh] overflow-auto flex items-center justify-center">
                <img id="lightbox-img" src="" alt="Preview Bukti Transfer" class="max-h-[80vh] max-w-full object-contain rounded-lg shadow-2xl">
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Modal Download Laporan
    function openDownloadReportModal() {
        document.getElementById('download-report-modal').classList.remove('hidden');
        if (window.refreshLucideIcons) window.refreshLucideIcons();
    }

    function closeDownloadReportModal() {
        document.getElementById('download-report-modal').classList.add('hidden');
    }

    function submitReportDownload(targetUrl) {
        const form = document.getElementById('download-report-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        form.action = targetUrl;
        form.submit();
        closeDownloadReportModal();
    }

    // Modal Bukti Transfer Harian
    function openDailyProofModal() {
        document.getElementById('daily-proof-modal').classList.remove('hidden');
        if (window.refreshLucideIcons) window.refreshLucideIcons();
    }

    function closeDailyProofModal() {
        document.getElementById('daily-proof-modal').classList.add('hidden');
    }

    // Lightbox Preview
    function openImagePreview(url, title) {
        document.getElementById('lightbox-img').src = url;
        document.getElementById('lightbox-title').innerText = title || 'Bukti Transfer';
        document.getElementById('lightbox-download-btn').href = url;
        document.getElementById('image-lightbox-modal').classList.remove('hidden');
        if (window.refreshLucideIcons) window.refreshLucideIcons();
    }

    function closeImagePreview() {
        document.getElementById('image-lightbox-modal').classList.add('hidden');
        document.getElementById('lightbox-img').src = '';
    }
</script>
@endpush