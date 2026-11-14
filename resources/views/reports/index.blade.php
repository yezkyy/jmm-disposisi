@extends('layouts.app')

@section('title', 'Laporan Realisasi Disposisi Pengeluaran')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-6 h-6 text-primary-600"></i>
                Laporan Realisasi Disposisi & Pengeluaran
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Rekapitulasi komprehensif pengajuan, persetujuan, dan pencairan kas perusahaan PT Jaya Mukti Mandiri.
            </p>
        </div>

        <div id="reports-export-btn" class="flex items-center gap-2">
            <a href="{{ route('reports.export-excel', request()->all()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md shadow-emerald-600/20">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                <span>Unduh Berkas Excel (.xlsx)</span>
            </a>
        </div>
    </div>

    <!-- 5 KPI Summary Cards -->
    <div id="reports-summary-cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Pengajuan -->
        <a href="{{ route('dispositions.index') }}" class="group bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-primary-300 transition block cursor-pointer">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-primary-600 transition-colors block mb-1">Total Pengajuan</span>
            <div class="text-base font-extrabold text-slate-800 font-mono truncate group-hover:text-primary-700 transition-colors">
                Rp {{ number_format($summary['total_requested'], 2, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400 mt-1 block">{{ $summary['count'] }} Pengajuan</span>
        </a>

        <!-- 2. Total Disetujui -->
        <a href="{{ route('dispositions.index', ['approval_status' => 'approved']) }}" class="group bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-teal-300 transition block cursor-pointer">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-teal-600 transition-colors block mb-1">Total Disetujui</span>
            <div class="text-base font-extrabold text-teal-600 font-mono truncate group-hover:text-teal-700 transition-colors">
                Rp {{ number_format($summary['total_approved'], 2, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400 mt-1 block">Otorisasi Disetujui</span>
        </a>

        <!-- 3. Total Dicairkan -->
        <a href="{{ route('payments.index') }}" class="group bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-300 transition block cursor-pointer">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors block mb-1">Total Dicairkan</span>
            <div class="text-base font-extrabold text-emerald-600 font-mono truncate group-hover:text-emerald-700 transition-colors">
                Rp {{ number_format($summary['total_paid'], 2, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400 mt-1 block">Pembayaran Posted</span>
        </a>

        <!-- 4. Sisa Belum Bayar -->
        <a href="{{ route('dispositions.index', ['payment_status' => 'unpaid']) }}" class="group bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-amber-300 transition block cursor-pointer">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-amber-600 transition-colors block mb-1">Sisa Tagihan</span>
            <div class="text-base font-extrabold text-amber-600 font-mono truncate group-hover:text-amber-700 transition-colors">
                Rp {{ number_format($summary['total_remaining'], 2, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400 mt-1 block">Kewajiban Pending</span>
        </a>

        <!-- 5. Rasio Pembayaran -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Rasio Pembayaran</span>
            <div class="text-base font-extrabold text-primary-700 font-mono truncate">
                {{ $summary['payment_rate'] }}%
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ min(100, $summary['payment_rate']) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Filter Multi-Kriteria -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('reports.index') }}" x-data="liveFilter({ baseUrl: '{{ route('reports.index') }}', targetId: 'reports-table-card', extraTargets: ['reports-summary-cards', 'reports-export-btn'] })" @submit.prevent="submitFilter()" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <!-- Rentang Tanggal Mulai -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Mulai Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" @change="submitFilter()" placeholder="Pilih tanggal mulai..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                </div>

                <!-- Rentang Tanggal Selesai -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" @change="submitFilter()" placeholder="Pilih tanggal selesai..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                </div>

                <!-- Divisi -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Divisi</label>
                    <select name="division_id" @change="submitFilter()" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                        <option value="">Semua Divisi</option>
                        @foreach ($divisions as $d)
                            <option value="{{ $d->id }}" {{ request('division_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Kategori -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Kategori Pengeluaran</label>
                    <select name="category_id" @change="submitFilter()" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Approval -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status Persetujuan</label>
                    <select name="approval_status" @change="submitFilter()" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                        <option value="">Semua Status Approval</option>
                        <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Disetujui Penuh</option>
                        <option value="partially_approved" {{ request('approval_status') === 'partially_approved' ? 'selected' : '' }}>Disetujui Sebagian</option>
                        <option value="waiting_manager" {{ request('approval_status') === 'waiting_manager' ? 'selected' : '' }}>Menunggu Manager</option>
                        <option value="waiting_finance" {{ request('approval_status') === 'waiting_finance' ? 'selected' : '' }}>Menunggu Finance</option>
                        <option value="waiting_owner" {{ request('approval_status') === 'waiting_owner' ? 'selected' : '' }}>Menunggu Owner</option>
                        <option value="revision_requested" {{ request('approval_status') === 'revision_requested' ? 'selected' : '' }}>Diminta Revisi</option>
                        <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                <!-- Status Pembayaran -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status Pembayaran</label>
                    <select name="payment_status" @change="submitFilter()" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                        <option value="">Semua Status Pembayaran</option>
                        <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                        <option value="partially_paid" {{ request('payment_status') === 'partially_paid' ? 'selected' : '' }}>Dibayar Sebagian</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Lunas Dibayar</option>
                    </select>
                </div>

                <!-- Pencarian Kata Kunci -->
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pencarian Nomor / Perihal / Penerima</label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci pencarian..." @input.debounce.350ms="submitFilter()" class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                    </div>
                </div>
            </div>

            <!-- Tombol Submit Filter -->
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" @click="resetFilter()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition flex items-center gap-1.5">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Reset Filter</span>
                </button>
                <span x-show="loading" class="text-xs font-semibold text-primary-600 flex items-center gap-1.5">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
                    <span>Memperbarui laporan...</span>
                </span>
            </div>
        </form>
    </div>

    <!-- Ringkasan Kategori Pengeluaran -->
    @if(count($categoryBreakdown) > 0)
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <i data-lucide="pie-chart" class="w-4 h-4 text-primary-600"></i>
                    Realisasi Anggaran Berdasarkan Kategori Biaya
                </span>
                <span class="text-[11px] text-slate-400">{{ count($categoryBreakdown) }} Kategori</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                @foreach ($categoryBreakdown as $cat)
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1 text-xs">
                        <span class="font-bold text-slate-800 block truncate">{{ $cat->category_name }}</span>
                        <div class="text-sm font-extrabold text-primary-700 font-mono">
                            Rp {{ number_format($cat->total_amount, 2, ',', '.') }}
                        </div>
                        <span class="text-[10px] text-slate-400 block">{{ $cat->item_count }} item transaksi</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Data Table Disposisi Sesuai Filter -->
    <div id="reports-table-card" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                Rincian Transaksi Hasil Filter ({{ $dispositions->total() }} Data)
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">No. Disposisi</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Pemohon</th>
                        <th class="py-3 px-4">Divisi</th>
                        <th class="py-3 px-4">Perihal</th>
                        <th class="py-3 px-4 text-right">Pengajuan</th>
                        <th class="py-3 px-4 text-right">Disetujui</th>
                        <th class="py-3 px-4 text-right">Dibayar</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($dispositions as $disp)
                        <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="window.location='{{ route('dispositions.show', $disp) }}'">
                            <td class="py-3 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                <span class="text-primary-600 hover:text-primary-700">
                                    {{ $disp->disposition_number ?: 'Draft #' . $disp->id }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600 whitespace-nowrap">
                                {{ $disp->disposition_date ? $disp->disposition_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-800">{{ $disp->applicant?->name }}</td>
                            <td class="py-3 px-4 text-slate-600">{{ $disp->division?->name ?? 'Operasional' }}</td>
                            <td class="py-3 px-4 max-w-xs truncate" title="{{ $disp->subject }}">{{ $disp->subject }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                Rp {{ number_format($disp->requested_total, 2, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-teal-700 whitespace-nowrap">
                                Rp {{ number_format($disp->final_approved_total ?? (in_array($disp->approval_status, ['approved', 'partially_approved']) ? $disp->requested_total : 0), 2, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                Rp {{ number_format($disp->paid_total, 2, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold
                                    @if($disp->approval_status === 'approved') bg-emerald-100 text-emerald-800
                                    @elseif($disp->approval_status === 'rejected') bg-rose-100 text-rose-800
                                    @elseif($disp->approval_status === 'revision_requested') bg-amber-100 text-amber-800
                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ $disp->approval_status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400 text-xs">
                                Tidak ada data disposisi yang cocok dengan kriteria filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($dispositions->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $dispositions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

