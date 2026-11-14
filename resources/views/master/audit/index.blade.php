@extends('layouts.app')

@section('title', 'Jejak Audit Aktivitas Sistem')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-6 h-6 text-primary-600"></i>
                Jejak Audit Aktivitas (Audit Trail)
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Catatan mutlak riwayat transaksi, perubahan data penting, otorisasi, dan login (Read-only).
            </p>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('master.audit.index') }}" 
              x-data="liveFilter({ baseUrl: '{{ route('master.audit.index') }}', targetId: 'audit-table-card' })"
              @submit.prevent="submitFilter()"
              class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <!-- Pencarian -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pencarian</label>
                <input type="text" name="search" value="{{ request('search') }}" @input.debounce.350ms="submitFilter()" placeholder="Action, IP, pengguna..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
            </div>

            <!-- Pengguna -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pengguna</label>
                <select name="user_id" @change="submitFilter()" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
                    <option value="">Semua Pengguna</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->role?->name }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Mulai Tanggal -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Mulai Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" @change="submitFilter()" placeholder="Pilih mulai tanggal..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-white">
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition flex items-center justify-center gap-1.5">
                    <span x-show="loading" class="animate-spin inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full" style="display: none;"></span>
                    <span>Filter</span>
                </button>
                <button type="button" @click="resetFilter()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div id="audit-table-card" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Waktu (WITA)</th>
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4">Aksi / Event</th>
                        <th class="py-3 px-4">Entitas Terkait</th>
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4 text-center">Data Perubahan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="viewDiff({{ json_encode($log->old_values) }}, {{ json_encode($log->new_values) }}, '{{ $log->action }}')">
                            <td class="py-3 px-4 text-slate-600 whitespace-nowrap font-mono text-[11px]">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800">{{ $log->user?->name ?? 'Sistem' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $log->user?->email ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold font-mono
                                    @if(str_contains($log->action, 'create') || str_contains($log->action, 'submit')) bg-emerald-100 text-emerald-800
                                    @elseif(str_contains($log->action, 'approved')) bg-teal-100 text-teal-800
                                    @elseif(str_contains($log->action, 'reversed') || str_contains($log->action, 'rejected')) bg-rose-100 text-rose-800
                                    @elseif(str_contains($log->action, 'update') || str_contains($log->action, 'revision')) bg-amber-100 text-amber-800
                                    @else bg-slate-100 text-slate-700 @endif">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600 text-[11px]">
                                {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                            </td>
                            <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                {{ $log->ip_address ?: '-' }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <button 
                                    type="button" 
                                    onclick="viewDiff({{ json_encode($log->old_values) }}, {{ json_encode($log->new_values) }}, '{{ $log->action }}')"
                                    class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                                >
                                    Lihat Nilai
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                                Belum ada catatan aktivitas yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal View JSON Diff -->
<div id="diff-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="code" class="w-4 h-4 text-primary-600"></i>
                <span id="diff-title">Rincian Nilai Audit</span>
            </h3>
            <button type="button" onclick="closeDiffModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Nilai Sebelum (Old):</span>
                <pre id="old-json" class="p-3 rounded-xl bg-slate-900 text-slate-200 font-mono text-[11px] overflow-x-auto whitespace-pre-wrap"></pre>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 block mb-1">Nilai Sesudah (New):</span>
                <pre id="new-json" class="p-3 rounded-xl bg-slate-900 text-emerald-400 font-mono text-[11px] overflow-x-auto whitespace-pre-wrap"></pre>
            </div>
        </div>
        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeDiffModal()" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-100">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function viewDiff(oldVal, newVal, action) {
        document.getElementById('diff-title').innerText = 'Data Mutasi: ' + action;
        document.getElementById('old-json').innerText = JSON.stringify(oldVal || {}, null, 2);
        document.getElementById('new-json').innerText = JSON.stringify(newVal || {}, null, 2);

        document.getElementById('diff-modal').classList.remove('hidden');
        document.getElementById('diff-modal').classList.add('flex');
    }

    function closeDiffModal() {
        document.getElementById('diff-modal').classList.remove('flex');
        document.getElementById('diff-modal').classList.add('hidden');
    }
</script>
@endpush

