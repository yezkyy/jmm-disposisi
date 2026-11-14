@extends('layouts.app')

@section('title', 'Master Rekening')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="wallet" class="w-6 h-6 text-primary-600"></i>
                Master Rekening
            </h1>
            <p class="text-xs text-slate-500 mt-1">Daftar rekening bank penerima pembayaran (karyawan, sopir, vendor, dan operasional).</p>
        </div>
        <div>
            <a href="{{ route('master.bank-accounts.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-xs shadow-sm transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Rekening</span>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('master.bank-accounts.index') }}" x-data="liveFilter({ baseUrl: '{{ route('master.bank-accounts.index') }}', targetId: 'bank-accounts-table-card' })" @submit.prevent="submitFilter()" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1">Cari Pemilik / No. Rekening</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Nama pemilik atau no. rekening..."
                        @input.debounce.350ms="submitFilter()"
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>

            <div>
                <label for="bank_id" class="block text-xs font-semibold text-slate-600 mb-1">Bank</label>
                <select id="bank_id" name="bank_id" @change="submitFilter()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                    <option value="">Semua Bank</option>
                    @foreach ($banks as $b)
                    <option value="{{ $b->id }}" {{ request('bank_id') == $b->id ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="owner_type" class="block text-xs font-semibold text-slate-600 mb-1">Jenis Pemilik</label>
                <select id="owner_type" name="owner_type" @change="submitFilter()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                    <option value="">Semua Jenis</option>
                    <option value="employee" {{ request('owner_type') === 'employee' ? 'selected' : '' }}>Karyawan</option>
                    <option value="driver" {{ request('owner_type') === 'driver' ? 'selected' : '' }}>Sopir</option>
                    <option value="vendor" {{ request('owner_type') === 'vendor' ? 'selected' : '' }}>Vendor</option>
                    <option value="other" {{ request('owner_type') === 'other' ? 'selected' : '' }}>Lainnya</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label for="status" class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select id="status" name="status" @change="submitFilter()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                        <option value="">Semua Status</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <button type="button" @click="resetFilter()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium text-xs rounded-xl transition shadow-sm h-[38px] flex items-center gap-1.5" title="Reset Filter">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Reset</span>
                </button>
                <span x-show="loading" class="text-xs font-semibold text-primary-600 flex items-center pb-2">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                </span>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div id="bank-accounts-table-card" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider text-[11px] font-semibold">
                        <th class="py-3.5 px-4">Nama Pemilik Rekening</th>
                        <th class="py-3.5 px-4">Bank & Nomor Rekening</th>
                        <th class="py-3.5 px-4">Jenis Pemilik</th>
                        <th class="py-3.5 px-4">Referensi / Catatan</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($accounts as $acc)
                    <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="window.location='{{ route('master.bank-accounts.edit', $acc) }}'">
                        <td class="py-3.5 px-4">
                            <div class="font-semibold text-slate-900">{{ $acc->owner_name }}</div>
                            <div class="text-[11px] text-slate-400">Didaftarkan: {{ $acc->created_at?->translatedFormat('d M Y') ?? '-' }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-medium text-slate-800">{{ $acc->bank_name }}</div>
                            <div class="font-mono text-slate-600 font-semibold tracking-wider text-[11px] select-all">
                                {{ $acc->account_number }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            @php
                            $typeBadge = match($acc->owner_type) {
                            'employee' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'driver' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'vendor' => 'bg-purple-50 text-purple-700 border-purple-200',
                            default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium border {{ $typeBadge }}">
                                {{ $acc->owner_type_label }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 max-w-xs">
                            @if($acc->owner_reference)
                            <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10px] mb-0.5">
                                Ref: {{ $acc->owner_reference }}
                            </span>
                            @endif
                            <div class="truncate text-[11px]">{{ $acc->notes ?? '-' }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            @if ($acc->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Aktif
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Nonaktif
                            </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right" onclick="event.stopPropagation()">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ route('master.bank-accounts.edit', $acc) }}" class="p-1.5 rounded-lg text-slate-600 hover:text-primary-600 hover:bg-slate-100 transition" title="Edit Rekening">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </a>

                                <form method="POST" action="{{ route('master.bank-accounts.toggle-status', $acc) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-1.5 rounded-lg transition {{ $acc->is_active ? 'text-slate-600 hover:text-rose-600 hover:bg-rose-50' : 'text-slate-600 hover:text-emerald-600 hover:bg-emerald-50' }}" title="{{ $acc->is_active ? 'Nonaktifkan Rekening' : 'Aktifkan Rekening' }}">
                                        <i data-lucide="{{ $acc->is_active ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('master.bank-accounts.destroy', $acc) }}" class="inline" onsubmit="return confirm('Hapus rekening {{ addslashes($acc->owner_name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus Rekening">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <p>Tidak ada rekening yang ditemukan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($accounts->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $accounts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection