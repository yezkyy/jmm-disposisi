@extends('layouts.app')

@section('title', 'Kotak Masuk Approval Disposisi')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="check-square" class="w-6 h-6 text-primary-600"></i>
                Kotak Masuk Persetujuan (Approval)
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Daftar pengajuan disposisi yang menunggu telaah dan keputusan Anda sesuai jenjang wewenang.
            </p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 text-xs font-semibold space-x-1">
        <a href="{{ route('approvals.index', ['tab' => 'pending']) }}" class="flex items-center gap-2 px-4 py-3 border-b-2 transition {{ $tab === 'pending' ? 'border-primary-600 text-primary-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            <i data-lucide="inbox" class="w-4 h-4"></i>
            <span>Perlu Ditinjau Saya</span>
            @if ($pendingCount > 0)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500 text-white">
                    {{ $pendingCount }}
                </span>
            @endif
        </a>

        <a href="{{ route('approvals.index', ['tab' => 'history']) }}" class="flex items-center gap-2 px-4 py-3 border-b-2 transition {{ $tab === 'history' ? 'border-primary-600 text-primary-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            <i data-lucide="clock" class="w-4 h-4"></i>
            <span>Riwayat Keputusan Saya</span>
        </a>

        <a href="{{ route('approvals.index', ['tab' => 'all']) }}" class="flex items-center gap-2 px-4 py-3 border-b-2 transition {{ $tab === 'all' ? 'border-primary-600 text-primary-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            <i data-lucide="layers" class="w-4 h-4"></i>
            <span>Semua Antrean Berjalan</span>
        </a>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between gap-4">
        <form method="GET" action="{{ route('approvals.index') }}" x-data="liveFilter({ baseUrl: '{{ route('approvals.index') }}', targetId: 'approvals-table-card' })" @submit.prevent="submitFilter()" class="flex-1 flex items-center gap-2 max-w-md">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari nomor disposisi, perihal, atau pemohon..." 
                    @input.debounce.350ms="submitFilter()"
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
            </div>
            <button type="button" @click="resetFilter()" class="px-3 py-2 bg-slate-100 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-200 transition flex items-center gap-1">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                <span>Reset</span>
            </button>
            <span x-show="loading" class="text-xs font-semibold text-primary-600 flex items-center gap-1">
                <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>
            </span>
        </form>

        <div class="text-xs text-slate-500">
            Total: <strong>{{ $dispositions->total() }}</strong> pengajuan
        </div>
    </div>

    <!-- Dispositions Table -->
    <div id="approvals-table-card" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">No. Disposisi</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Pemohon / Divisi</th>
                        <th class="py-3 px-4">Perihal</th>
                        <th class="py-3 px-4 text-right">Nominal Pengajuan</th>
                        <th class="py-3 px-4 text-center">Status Tahap</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($dispositions as $disp)
                        @php
                            $user = auth()->user();
                            $canReviewThis = ($tab === 'pending') && ($disp->applicant_id !== $user->id);
                            $targetUrl = $canReviewThis ? route('approvals.review', $disp) : route('dispositions.show', $disp);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="window.location='{{ $targetUrl }}'">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
                                {{ $disp->disposition_number ?: 'Draft #' . $disp->id }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                {{ $disp->disposition_date ? $disp->disposition_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800">{{ $disp->applicant?->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $disp->division?->name ?? 'Tanpa Divisi' }}</div>
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <p class="font-medium text-slate-800 truncate" title="{{ $disp->subject }}">
                                    {{ $disp->subject }}
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5 truncate">{{ $disp->recipients->pluck('recipient_name')->join(', ') }}</p>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                Rp {{ number_format($disp->requested_total, 2, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold
                                    @if($disp->approval_status === 'approved') bg-emerald-100 text-emerald-800
                                    @elseif($disp->approval_status === 'partially_approved') bg-teal-100 text-teal-800
                                    @elseif($disp->approval_status === 'rejected') bg-rose-100 text-rose-800
                                    @elseif($disp->approval_status === 'revision_requested') bg-amber-100 text-amber-800
                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ $disp->approval_status_label }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap" onclick="event.stopPropagation()">
                                @if ($canReviewThis)
                                    <a href="{{ route('approvals.review', $disp) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition shadow-sm">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                        <span>Telaah & Putuskan</span>
                                    </a>
                                @else
                                    <a href="{{ route('dispositions.show', $disp) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>Detail</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <i data-lucide="inbox" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">Tidak ada pengajuan disposisi</p>
                                <p class="text-xs text-slate-400 mt-1">Saat ini tidak ada data pada antrean ini.</p>
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

