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
        <div>
            <a href="{{ route('dispositions.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-xs shadow-sm hover:shadow transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Buat Pengajuan Baru</span>
            </a>
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
                        <option value="waiting_finance" {{ request('approval_status') === 'waiting_finance' ? 'selected' : '' }}>Verifikasi Finance</option>
                        <option value="waiting_owner" {{ request('approval_status') === 'waiting_owner' ? 'selected' : '' }}>Menunggu Owner</option>
                        <option value="revision_requested" {{ request('approval_status') === 'revision_requested' ? 'selected' : '' }}>Perlu Revisi</option>
                        <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Disetujui Final</option>
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
</div>
@endsection