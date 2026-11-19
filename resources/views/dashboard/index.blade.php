@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header Card -->
    <div class="bg-gradient-to-r from-primary-900 via-primary-800 to-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-primary-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-white text-xs font-medium backdrop-blur-sm border border-white/10">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sistem Aktif &bull; Role: {{ auth()->user()->role?->display_name }}</span>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white">
                    Selamat Datang, {{ auth()->user()->name }}
                </h1>
                <p class="text-slate-300 text-xs md:text-sm max-w-2xl leading-relaxed">
                    Sistem Digital Pengelolaan Disposisi Pengeluaran <strong>PT Jaya Mukti Mandiri</strong>. Pantau antrean persetujuan, realisasi pembayaran, dan performa keuangan operasional.
                </p>
            </div>

            <!-- Quick Actions Toolbar -->
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('dispositions.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-primary-900 hover:bg-slate-100 font-bold text-xs transition shadow-sm">
                    <i data-lucide="plus" class="w-4 h-4 text-primary-700"></i>
                    <span>Ajukan Disposisi</span>
                </a>

                @if (auth()->user()->isManager() || auth()->user()->isAdminFinance() || auth()->user()->isOwner())
                    <a href="{{ route('approvals.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-700/80 hover:bg-primary-700 text-white font-semibold text-xs border border-primary-500/40 transition">
                        <i data-lucide="check-square" class="w-4 h-4 text-sky-300"></i>
                        <span>Kotak Approval</span>
                    </a>
                @endif

                @if (auth()->user()->isAdminFinance() || auth()->user()->isOwner())
                    <a href="{{ route('payments.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-700/80 hover:bg-emerald-600 text-white font-semibold text-xs border border-emerald-500/40 transition">
                        <i data-lucide="receipt" class="w-4 h-4 text-emerald-300"></i>
                        <span>Catat Pembayaran</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Summary Harian Hari Ini (Revisi Versi 3 Poin 5) -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 mb-4 border-b border-slate-100 gap-2">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </span>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Ringkasan Aktivitas Disposisi Hari Ini</h2>
                    <p class="text-[11px] text-slate-400">{{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
            </div>
            <a href="{{ route('dispositions.index', ['start_date' => now()->toDateString(), 'end_date' => now()->toDateString()]) }}" class="text-[11px] font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                <span>Lihat Disposisi Hari Ini</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-[11px] font-bold uppercase text-slate-400 block mb-1">Disposisi Diajukan Hari Ini</span>
                <div class="text-2xl font-black text-slate-800 font-mono">{{ $todayCount }} <span class="text-xs font-normal text-slate-500 font-sans">Berkas</span></div>
            </div>
            <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100">
                <span class="text-[11px] font-bold uppercase text-blue-700 block mb-1">Total Nominal Pengajuan Hari Ini</span>
                <div class="text-2xl font-black text-blue-900 font-mono">Rp {{ number_format($todayNominal, 0, ',', '.') }}</div>
            </div>
            <div class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
                <span class="text-[11px] font-bold uppercase text-emerald-700 block mb-1">Total Disetujui Hari Ini</span>
                <div class="text-2xl font-black text-emerald-900 font-mono">Rp {{ number_format($todayApproved, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- 4 Live Financial KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Pengajuan -->
        <a href="{{ route('dispositions.index') }}" class="group block bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md hover:border-primary-300 transition cursor-pointer">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-primary-600 transition-colors">Total Pengajuan</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 group-hover:bg-primary-600 group-hover:text-white transition flex items-center justify-center">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-extrabold text-slate-800 font-mono truncate group-hover:text-primary-700 transition-colors">
                    Rp {{ number_format($totalRequested, 2, ',', '.') }}
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                    <span>{{ $totalDispositions }} pengajuan terdaftar</span>
                    <span class="text-primary-600 font-semibold text-[11px] inline-flex items-center gap-0.5">
                        <span>Lihat</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 group-hover:translate-x-0.5 transition-transform"></i>
                    </span>
                </p>
            </div>
        </a>

        <!-- 2. Total Disetujui -->
        <a href="{{ route('dispositions.index', ['approval_status' => 'approved']) }}" class="group block bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md hover:border-teal-300 transition cursor-pointer">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-teal-600 transition-colors">Total Disetujui</span>
                <span class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition flex items-center justify-center">
                    <i data-lucide="check-check" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-extrabold text-teal-700 font-mono truncate group-hover:text-teal-800 transition-colors">
                    Rp {{ number_format($totalApproved, 2, ',', '.') }}
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                    <span>{{ $approvedDispositions }} disetujui resmi</span>
                    <span class="text-teal-700 font-semibold text-[11px] inline-flex items-center gap-0.5">
                        <span>Detail</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 group-hover:translate-x-0.5 transition-transform"></i>
                    </span>
                </p>
            </div>
        </a>

        <!-- 3. Realisasi Pembayaran -->
        <a href="{{ route('payments.index') }}" class="group block bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-300 transition cursor-pointer">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Realisasi Dicairkan</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition flex items-center justify-center">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-extrabold text-emerald-600 font-mono truncate group-hover:text-emerald-700 transition-colors">
                    Rp {{ number_format($totalPaid, 2, ',', '.') }}
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                    <span>Pembayaran terkonfirmasi</span>
                    <span class="text-emerald-700 font-semibold text-[11px] inline-flex items-center gap-0.5">
                        <span>Voucher</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 group-hover:translate-x-0.5 transition-transform"></i>
                    </span>
                </p>
            </div>
        </a>

        <!-- 4. Sisa Kewajiban Belum Terbayar -->
        <a href="{{ route('dispositions.index', ['payment_status' => 'unpaid']) }}" class="group block bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md hover:border-amber-300 transition cursor-pointer">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 group-hover:text-amber-600 transition-colors">Sisa Belum Bayar</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-extrabold text-amber-600 font-mono truncate group-hover:text-amber-700 transition-colors">
                    Rp {{ number_format($totalRemaining, 2, ',', '.') }}
                </div>
                <p class="text-xs text-slate-500 mt-1 flex items-center justify-between">
                    <span>Kewajiban berjalan</span>
                    <span class="text-amber-700 font-semibold text-[11px] inline-flex items-center gap-0.5">
                        <span>Antrean</span>
                        <i data-lucide="arrow-right" class="w-3 h-3 group-hover:translate-x-0.5 transition-transform"></i>
                    </span>
                </p>
            </div>
        </a>
    </div>

    <!-- Charts Row (ApexCharts) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart 1: Tren Keuangan (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                        <i data-lucide="trending-up" class="w-4 h-4 text-primary-600"></i>
                        Tren Pengajuan vs Realisasi Pembayaran (6 Bulan Terakhir)
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Komparasi nilai pengeluaran yang diajukan dan dicairkan</p>
                </div>
            </div>
            <div id="chart-timeline" class="w-full h-72"></div>
        </div>

        <!-- Chart 2: Komposisi Pengeluaran (1 Col) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4 flex flex-col justify-between">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2 pb-3 border-b border-slate-100">
                    <i data-lucide="pie-chart" class="w-4 h-4 text-primary-600"></i>
                    Kategori Pengeluaran Terbesar
                </h2>
                <p class="text-[11px] text-slate-400 mt-2">Distribusi alokasi anggaran berdasarkan kategori biaya</p>
            </div>
            <div id="chart-category" class="w-full h-64 flex items-center justify-center"></div>
        </div>
    </div>

    <!-- Action Center: Tugas Menunggu Tindakan Saya vs Disposisi Terbaru -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 1. Tugas Menunggu Tindakan Saya -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-amber-500"></i>
                    Tugas Menunggu Tindakan Saya
                </h2>
                <span class="text-[11px] font-extrabold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                    {{ $pendingTasks->count() }} Antrean
                </span>
            </div>

            <div class="space-y-3">
                @forelse ($pendingTasks as $task)
                    @php
                        $taskUrl = ($task->approval_status === 'draft' || $task->approval_status === 'revision_requested')
                            ? route('dispositions.edit', $task)
                            : route('approvals.review', $task);
                    @endphp
                    <a href="{{ $taskUrl }}" class="group block p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white hover:border-primary-300 hover:shadow-sm transition flex items-center justify-between gap-3 text-xs cursor-pointer">
                        <div class="min-w-0 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-slate-800 group-hover:text-primary-700 transition-colors">{{ $task->disposition_number ?: 'Draft #' . $task->id }}</span>
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold
                                    @if($task->approval_status === 'revision_requested') bg-amber-100 text-amber-800
                                    @elseif($task->approval_status === 'draft') bg-slate-100 text-slate-700
                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ $task->approval_status_label }}
                                </span>
                            </div>
                            <p class="text-slate-700 font-medium truncate group-hover:text-slate-900">{{ $task->subject }}</p>
                            <p class="text-[10px] text-slate-400">Pemohon: {{ $task->applicant?->name }} &bull; Rp {{ number_format($task->requested_total, 2, ',', '.') }}</p>
                        </div>

                        <div>
                            @if ($task->approval_status === 'draft' || $task->approval_status === 'revision_requested')
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-500 group-hover:bg-amber-600 text-white font-bold text-xs transition shadow-sm">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                    <span>Revisi</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary-600 group-hover:bg-primary-700 text-white font-bold text-xs transition shadow-sm">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Putuskan</span>
                                </span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="py-8 text-center text-slate-400">
                        <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-500 mx-auto mb-2"></i>
                        <p class="text-xs font-semibold text-slate-600">Tidak ada tugas pending</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Semua antrean tindakan untuk akun Anda telah terselesaikan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- 2. Aktivitas Disposisi Terbaru -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-primary-600"></i>
                    Aktivitas Disposisi Terbaru
                </h2>
                <a href="{{ route('dispositions.index') }}" class="text-[11px] font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                    <span>Lihat Semua</span>
                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </a>
            </div>

            <div class="space-y-3">
                @forelse ($recentDispositions as $rd)
                    <a href="{{ route('dispositions.show', $rd) }}" class="group block p-3.5 rounded-xl border border-slate-100 hover:border-primary-200 hover:bg-slate-50/80 transition flex items-center justify-between gap-3 text-xs cursor-pointer">
                        <div class="min-w-0 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-primary-600 group-hover:text-primary-700">
                                    {{ $rd->disposition_number ?: 'Draft #' . $rd->id }}
                                </span>
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold
                                    @if($rd->approval_status === 'approved') bg-emerald-100 text-emerald-800
                                    @elseif($rd->approval_status === 'rejected') bg-rose-100 text-rose-800
                                    @elseif($rd->approval_status === 'revision_requested') bg-amber-100 text-amber-800
                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ $rd->approval_status_label }}
                                </span>
                            </div>
                            <p class="text-slate-800 font-medium truncate group-hover:text-primary-900">{{ $rd->subject }}</p>
                            <p class="text-[10px] text-slate-400">{{ $rd->applicant?->name }} ({{ $rd->division?->name ?? 'Operasional' }}) &bull; {{ $rd->disposition_date?->format('d/m/Y') }}</p>
                        </div>

                        <div class="text-right whitespace-nowrap">
                            <span class="font-mono font-extrabold text-slate-800 text-xs block group-hover:text-primary-700 transition-colors">
                                Rp {{ number_format($rd->requested_total, 2, ',', '.') }}
                            </span>
                            <span class="text-[10px] font-semibold
                                @if($rd->payment_status === 'paid') text-emerald-600
                                @elseif($rd->payment_status === 'partially_paid') text-amber-600
                                @else text-slate-400 @endif">
                                {{ $rd->payment_status_label }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="py-8 text-center text-slate-400">
                        <p class="text-xs font-semibold text-slate-600">Belum ada transaksi disposisi</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Rekapitulasi Disposisi Harian Berdasarkan Tanggal (Revisi Versi 3 Poin 5) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <i data-lucide="calendar-days" class="w-4 h-4 text-primary-600"></i>
                    Rekapitulasi Disposisi Harian Berdasarkan Tanggal
                </h2>
                <p class="text-[11px] text-slate-400 mt-0.5">Monitoring total nominal pengajuan, persetujuan, dan pencairan harian</p>
            </div>
            <a href="{{ route('reports.index') }}" class="text-[11px] font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                <span>Laporan Lengkap</span>
                <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
        </div>

        <div class="overflow-x-auto -mx-6 px-6">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="py-2.5 px-3">Tanggal Disposisi</th>
                        <th class="py-2.5 px-3 text-center">Jumlah Pengajuan</th>
                        <th class="py-2.5 px-3 text-right">Total Nominal Diajukan</th>
                        <th class="py-2.5 px-3 text-right">Total Nominal Disetujui</th>
                        <th class="py-2.5 px-3 text-right">Realisasi Dibayar</th>
                        <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($dailySummary as $day)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-3 font-semibold text-slate-800">
                                {{ \Carbon\Carbon::parse($day->disposition_date)->translatedFormat('l, d F Y') }}
                                @if ($day->disposition_date === now()->toDateString())
                                    <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">Hari Ini</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full font-bold text-xs bg-slate-100 text-slate-700">
                                    {{ $day->total_count }} Berkas
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                Rp {{ number_format((float) $day->total_requested, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-primary-700">
                                Rp {{ number_format((float) $day->total_approved, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono text-emerald-600 font-bold">
                                Rp {{ number_format((float) $day->total_paid, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <a href="{{ route('dispositions.index', ['start_date' => $day->disposition_date, 'end_date' => $day->disposition_date]) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-600 hover:text-primary-800 hover:underline">
                                    <span>Rincian</span>
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400 text-xs">
                                Belum ada data rekapitulasi harian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Line/Area Spline Chart (Tren Pengajuan & Realisasi)
        const timelineOptions = {
            series: [
                {
                    name: 'Pengajuan (Rp)',
                    data: @json($trendRequested)
                },
                {
                    name: 'Realisasi Dicairkan (Rp)',
                    data: @json($trendPaid)
                }
            ],
            chart: {
                type: 'area',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            colors: ['#026ec4', '#10b981'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 95, 100]
                }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2.5 },
            xaxis: {
                categories: @json($months),
                labels: { style: { fontSize: '11px', colors: '#64748b' } }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        if (value === 0) return 'Rp 0';
                        if (value >= 1000000000) {
                            const val = value / 1000000000;
                            return 'Rp ' + (val % 1 === 0 ? val.toFixed(0) : val.toFixed(1)).replace('.', ',') + ' M';
                        } else if (value >= 1000000) {
                            const val = value / 1000000;
                            return 'Rp ' + (val % 1 === 0 ? val.toFixed(0) : val.toFixed(1)).replace('.', ',') + ' JT';
                        } else if (value >= 1000) {
                            const val = value / 1000;
                            return 'Rp ' + (val % 1 === 0 ? val.toFixed(0) : val.toFixed(1)).replace('.', ',') + ' RB';
                        }
                        return 'Rp ' + value.toLocaleString('id-ID');
                    },
                    style: { fontSize: '11px', colors: '#64748b' }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return 'Rp ' + val.toLocaleString('id-ID', { minimumFractionDigits: 2 });
                    }
                }
            },
            legend: { position: 'top', horizontalAlign: 'right' }
        };

        const timelineEl = document.querySelector("#chart-timeline");
        if (timelineEl) {
            new ApexCharts(timelineEl, timelineOptions).render();
        }

        // 2. Donut Chart (Kategori Pengeluaran)
        const categoryOptions = {
            series: @json($categoryTotals),
            labels: @json($categoryLabels),
            chart: {
                type: 'donut',
                height: 240,
                fontFamily: 'inherit'
            },
            colors: ['#026ec4', '#0d9488', '#f59e0b', '#8b5cf6', '#ec4899'],
            dataLabels: { enabled: false },
            legend: { position: 'bottom', fontSize: '11px' },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return 'Rp ' + val.toLocaleString('id-ID', { minimumFractionDigits: 2 });
                    }
                }
            }
        };

        const catEl = document.querySelector("#chart-category");
        if (catEl) {
            new ApexCharts(catEl, categoryOptions).render();
        }
    });
</script>
@endpush
