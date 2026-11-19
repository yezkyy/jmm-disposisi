@extends('layouts.app')

@section('title', 'Edit Rekening: ' . $bankAccount->owner_name)

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="edit-2" class="w-6 h-6 text-primary-600"></i>
                Edit Data Rekening
            </h1>
            <p class="text-xs text-slate-500 mt-1">Perbarui nomor rekening, nama bank, atau pemilik rekening.</p>
        </div>
        <a href="{{ route('master.bank-accounts.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">
        <form method="POST" action="{{ route('master.bank-accounts.update', $bankAccount) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Nama Pemilik Rekening -->
            <div>
                <label for="owner_name" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Nama Pemilik Rekening <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="owner_name" 
                    name="owner_name" 
                    value="{{ old('owner_name', $bankAccount->owner_name) }}" 
                    required 
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('owner_name') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                @error('owner_name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Pilihan Bank -->
                <div>
                    <label for="bank_id" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Pilih Bank Terdaftar
                    </label>
                    <select id="bank_id" name="bank_id" onchange="syncBankName(this)" class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('bank_id') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                        <option value="">-- Bank Kustom / Lainnya --</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}" data-name="{{ $bank->name }}" {{ old('bank_id', $bankAccount->bank_id) == $bank->id ? 'selected' : '' }}>
                                {{ $bank->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Input Nama Bank -->
                <div>
                    <label for="bank_name" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Nama Bank <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="bank_name" 
                        name="bank_name" 
                        value="{{ old('bank_name', $bankAccount->bank_name) }}" 
                        required 
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('bank_name') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                    @error('bank_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nomor Rekening -->
                <div>
                    <label for="account_number" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Nomor Rekening <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="account_number" 
                        name="account_number" 
                        value="{{ old('account_number', $bankAccount->account_number) }}" 
                        required 
                        class="w-full px-3.5 py-2.5 text-xs rounded-xl border font-mono @error('account_number') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                    @error('account_number')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jenis Pemilik -->
                <div>
                    <label for="owner_type" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Jenis Pemilik <span class="text-rose-500">*</span>
                    </label>
                    <select id="owner_type" name="owner_type" required class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('owner_type') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                        <option value="company" {{ old('owner_type', $bankAccount->owner_type) === 'company' ? 'selected' : '' }}>Perusahaan (PT JMM)</option>
                        <option value="employee" {{ old('owner_type', $bankAccount->owner_type) === 'employee' ? 'selected' : '' }}>Karyawan</option>
                        <option value="driver" {{ old('owner_type', $bankAccount->owner_type) === 'driver' ? 'selected' : '' }}>Sopir</option>
                        <option value="vendor" {{ old('owner_type', $bankAccount->owner_type) === 'vendor' ? 'selected' : '' }}>Vendor</option>
                        <option value="other" {{ old('owner_type', $bankAccount->owner_type) === 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('owner_type')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Referensi Pemilik -->
            <div>
                <label for="owner_reference" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Referensi Pemilik (Opsional)
                </label>
                <input 
                    type="text" 
                    id="owner_reference" 
                    name="owner_reference" 
                    value="{{ old('owner_reference', $bankAccount->owner_reference) }}" 
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('owner_reference') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
            </div>

            <!-- Catatan -->
            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Catatan Tambahan
                </label>
                <textarea 
                    id="notes" 
                    name="notes" 
                    rows="2" 
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border @error('notes') border-rose-500 bg-rose-50/20 @else border-slate-200 @enderror focus:outline-none focus:ring-2 focus:ring-primary-500"
                >{{ old('notes', $bankAccount->notes) }}</textarea>
            </div>

            <!-- Status Aktif -->
            <div>
                <label class="flex items-start sm:items-center gap-3.5 p-3.5 rounded-xl border border-slate-200/90 bg-slate-50/60 hover:bg-slate-50 hover:border-slate-300 transition cursor-pointer select-none group">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $bankAccount->is_active) ? 'checked' : '' }} class="mt-0.5 sm:mt-0">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800 group-hover:text-primary-700 transition">Rekening Aktif</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Rekening nonaktif tidak dapat dipilih saat pengajuan disposisi baru, namun arsip transaksi historis tetap utuh.</p>
                    </div>
                </label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('master.bank-accounts.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold text-xs shadow-md shadow-primary-600/30 transition flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Perbarui Rekening</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function syncBankName(selectElement) {
        const selected = selectElement.options[selectElement.selectedIndex];
        const bankName = selected.getAttribute('data-name');
        if (bankName) {
            document.getElementById('bank_name').value = bankName;
        }
    }
</script>
@endpush

