@extends('layouts.app')

@section('title', 'Master Kategori Pengeluaran')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="tags" class="w-6 h-6 text-primary-600"></i>
                Master Kategori Pengeluaran
            </h1>
            <p class="text-xs text-slate-500 mt-1">Klasifikasi anggaran pengeluaran untuk standarisasi rincian disposisi.</p>
        </div>
        <div>
            <a href="{{ route('master.categories.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-xs shadow-sm transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Kategori</span>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('master.categories.index') }}" x-data="liveFilter({ baseUrl: '{{ route('master.categories.index') }}', targetId: 'categories-table-card' })" @submit.prevent="submitFilter()" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1">Cari Nama / Kode / Deskripsi</label>
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Ketik kata kunci kategori..."
                        @input.debounce.350ms="submitFilter()"
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
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
    <div id="categories-table-card" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase tracking-wider text-[11px] font-semibold">
                        <th class="py-3.5 px-4">Nama Kategori</th>
                        <th class="py-3.5 px-4">Kode</th>
                        <th class="py-3.5 px-4">Deskripsi</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($categories as $cat)
                    <tr class="hover:bg-slate-50/80 transition cursor-pointer" onclick="window.location='{{ route('master.categories.edit', $cat) }}'">
                        <td class="py-3.5 px-4 font-semibold text-slate-900">
                            {{ $cat->name }}
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-primary-50 text-primary-700 border border-primary-200">
                                {{ $cat->code ?? '-' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 max-w-sm truncate">
                            {{ $cat->description ?? '-' }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if ($cat->is_active)
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
                                <a href="{{ route('master.categories.edit', $cat) }}" class="p-1.5 rounded-lg text-slate-600 hover:text-primary-600 hover:bg-slate-100 transition" title="Edit Kategori">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </a>

                                <form method="POST" action="{{ route('master.categories.toggle-status', $cat) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-1.5 rounded-lg transition {{ $cat->is_active ? 'text-slate-600 hover:text-rose-600 hover:bg-rose-50' : 'text-slate-600 hover:text-emerald-600 hover:bg-emerald-50' }}" title="{{ $cat->is_active ? 'Nonaktifkan Kategori' : 'Aktifkan Kategori' }}">
                                        <i data-lucide="{{ $cat->is_active ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i>
                                    </button>
                                </form>

                                <form id="delete-cat-{{ $cat->id }}" method="POST" action="{{ route('master.categories.destroy', $cat) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDeleteCategory('{{ $cat->id }}', '{{ addslashes($cat->name) }}')" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus Kategori">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            <p>Tidak ada kategori pengeluaran yang ditemukan.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
        <div class="p-4 border-t border-slate-100">
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    function confirmDeleteCategory(id, name) {
        Swal.fire({
            title: 'Hapus Kategori?',
            text: `Kategori "${name}" akan dihapus permanen dari sistem.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Kategori',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-cat-' + id).submit();
            }
        });
    }
</script>
@endpush
@endsection