@extends('layouts.app')

@section('title', 'Tambah Kategori Pengeluaran')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-6 h-6 text-primary-600"></i>
                Tambah Kategori Pengeluaran
            </h1>
            <p class="text-xs text-slate-500 mt-1">Daftarkan kategori anggaran baru untuk standarisasi item disposisi.</p>
        </div>
        <a href="{{ route('master.categories.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">
        <form method="POST" action="{{ route('master.categories.store') }}" class="space-y-5">
            @csrf

            <!-- Nama Kategori -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Nama Kategori Pengeluaran <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}" 
                    required 
                    placeholder="Contoh: Pemeliharaan Kendaraan"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('name') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Kode Kategori -->
            <div>
                <label for="code" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Kode Kategori
                </label>
                <input 
                    type="text" 
                    id="code" 
                    name="code" 
                    value="{{ old('code') }}" 
                    placeholder="Contoh: ARM"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('code') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500 uppercase"
                >
                @error('code')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Deskripsi -->
            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Deskripsi / Cakupan Pengeluaran
                </label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="3" 
                    placeholder="Keterangan jenis biaya yang masuk ke kategori ini..."
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('description') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                >{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status Aktif -->
            <div>
                <label class="flex items-start sm:items-center gap-3.5 p-3.5 rounded-xl border border-slate-200/90 bg-slate-50/60 hover:bg-slate-50 hover:border-slate-300 transition cursor-pointer select-none group">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="mt-0.5 sm:mt-0">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800 group-hover:text-primary-700 transition">Kategori Aktif</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Kategori aktif dapat dipilih oleh pemohon saat mengajukan disposisi baru.</p>
                    </div>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('master.categories.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-xs shadow-md shadow-primary-600/30 transition flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan Kategori</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

