@extends('layouts.app')

@section('title', 'Backup & Pemulihan Sistem')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="database-backup" class="w-6 h-6 text-primary-600"></i>
                Backup & Pemulihan Sistem (Disaster Recovery)
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Cadangkan data SQL, lampiran dokumen, dan paket pemulihan menyeluruh PT Jaya Mukti Mandiri.
            </p>
        </div>
    </div>

    <!-- 3 Backup Action Trigger Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- 1. SQL Database Backup -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4 hover:shadow transition">
            <div class="space-y-2">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="database" class="w-5 h-5"></i>
                </div>
                <h2 class="text-sm font-bold text-slate-800">1. Backup SQL Database</h2>
                <p class="text-xs text-slate-500">Mengekspor seluruh tabel master, disposisi, approval, dan mutasi finansial ke file SQL murni.</p>
            </div>

            <form method="POST" action="{{ route('master.backups.store') }}">
                @csrf
                <input type="hidden" name="type" value="sql">
                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs transition flex items-center justify-center gap-2">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Buat Dump Database SQL</span>
                </button>
            </form>
        </div>

        <!-- 2. Attachments Backup -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4 hover:shadow transition">
            <div class="space-y-2">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="paperclip" class="w-5 h-5"></i>
                </div>
                <h2 class="text-sm font-bold text-slate-800">2. Backup Berkas & Bukti</h2>
                <p class="text-xs text-slate-500">Mengarsipkan seluruh file lampiran PDF dan bukti transfer pembayaran ke arsip ZIP.</p>
            </div>

            <form method="POST" action="{{ route('master.backups.store') }}">
                @csrf
                <input type="hidden" name="type" value="attachments">
                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs transition flex items-center justify-center gap-2">
                    <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                    <span>Buat Arsip Berkas (ZIP)</span>
                </button>
            </form>
        </div>

        <!-- 3. Full Disaster Recovery Package -->
        <div class="bg-white rounded-2xl p-5 border border-primary-200 bg-primary-50/20 shadow-sm flex flex-col justify-between space-y-4 hover:shadow transition">
            <div class="space-y-2">
                <div class="w-10 h-10 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <h2 class="text-sm font-bold text-slate-800">3. Paket Pemulihan Penuh</h2>
                <p class="text-xs text-slate-500">Paket pemulihan lengkap (Database SQL + Seluruh Lampiran + Manifest SHA-256) untuk disaster recovery.</p>
            </div>

            <form method="POST" action="{{ route('master.backups.store') }}">
                @csrf
                <input type="hidden" name="type" value="zip">
                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-bold text-xs transition flex items-center justify-center gap-2 shadow-sm">
                    <i data-lucide="package" class="w-3.5 h-3.5"></i>
                    <span>Generate Paket Penuh (ZIP)</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Riwayat Berkas Backup -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                Riwayat Arsip Cadangan (Backup History)
            </h2>
            <span class="text-xs text-slate-400">Total: {{ $backups->total() }} Berkas</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Tipe Cadangan</th>
                        <th class="py-3 px-4">Nama Berkas</th>
                        <th class="py-3 px-4">Ukuran</th>
                        <th class="py-3 px-4">Dibuat Oleh</th>
                        <th class="py-3 px-4">Waktu Dibuat</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi & Integritas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($backups as $b)
                        <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="window.location='{{ route('master.backups.download', $b) }}'">
                            <td class="py-3 px-4 font-semibold uppercase text-[11px] text-slate-700">
                                @if($b->backup_type === 'sql')
                                    <span class="inline-flex items-center gap-1 text-blue-700 font-bold">
                                        <i data-lucide="database" class="w-3.5 h-3.5"></i> SQL Dump
                                    </span>
                                @elseif($b->backup_type === 'attachments')
                                    <span class="inline-flex items-center gap-1 text-amber-700 font-bold">
                                        <i data-lucide="paperclip" class="w-3.5 h-3.5"></i> Lampiran
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-primary-700 font-bold">
                                        <i data-lucide="package" class="w-3.5 h-3.5"></i> Paket Penuh
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-slate-800 text-[11px]">
                                {{ $b->file_name }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ $b->formatted_size }}
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                {{ $b->creator?->name ?? 'Sistem' }}
                            </td>
                            <td class="py-3 px-4 text-slate-500 text-[11px] whitespace-nowrap">
                                {{ $b->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold
                                    @if($b->status === 'completed') bg-emerald-100 text-emerald-800
                                    @else bg-rose-100 text-rose-800 @endif">
                                    {{ strtoupper($b->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-center gap-2">
                                    @if ($b->status === 'completed')
                                        <a href="{{ route('master.backups.download', $b) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 text-xs font-semibold transition" title="Unduh File">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            <span>Unduh</span>
                                        </a>

                                        <form method="POST" action="{{ route('master.backups.verify', $b) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition" title="Uji Integritas Arsip">
                                                <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                <span>Uji Validitas</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                Belum ada arsip cadangan yang dibuat. Klik salah satu tombol di atas untuk membuat backup pertama.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($backups->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $backups->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

