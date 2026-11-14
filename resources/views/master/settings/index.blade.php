@extends('layouts.app')

@section('title', 'Pengaturan Identitas Perusahaan & Format Disposisi')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="settings" class="w-6 h-6 text-primary-600"></i>
                Pengaturan Perusahaan & Format Nomor
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola identitas resmi PT Jaya Mukti Mandiri yang tercetak pada kop surat disposisi, voucher, dan laporan.
            </p>
        </div>
    </div>

    <!-- Form Container -->
    <form method="POST" action="{{ route('master.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Identitas Perusahaan -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="building-2" class="w-4 h-4 text-primary-600"></i>
                1. Identitas Resmi Perusahaan
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="company_name" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Nama Resmi Perusahaan <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="company_name" 
                        name="company_name" 
                        value="{{ old('company_name', $settings['company_name']) }}" 
                        required 
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 font-bold"
                    >
                    @error('company_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="company_tagline" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Tagline / Bidang Usaha Perusahaan
                    </label>
                    <input 
                        type="text" 
                        id="company_tagline" 
                        name="company_tagline" 
                        value="{{ old('company_tagline', $settings['company_tagline']) }}" 
                        placeholder="Contoh: General Contractor, Heavy Equipment & Logistics Services"
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                </div>

                <div class="sm:col-span-2">
                    <label for="company_address" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Alamat Kantor Operasional <span class="text-rose-500">*</span>
                    </label>
                    <textarea 
                        id="company_address" 
                        name="company_address" 
                        rows="2" 
                        required 
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >{{ old('company_address', $settings['company_address']) }}</textarea>
                    @error('company_address') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company_phone" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Nomor Telepon Kantor <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="company_phone" 
                        name="company_phone" 
                        value="{{ old('company_phone', $settings['company_phone']) }}" 
                        required 
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                    @error('company_phone') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="company_email" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Email Resmi Finance / Administrasi <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="company_email" 
                        name="company_email" 
                        value="{{ old('company_email', $settings['company_email']) }}" 
                        required 
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono"
                    >
                    @error('company_email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <!-- Format Penomoran Disposisi Otomatis -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="hash" class="w-4 h-4 text-primary-600"></i>
                2. Format Penomoran Disposisi Otomatis
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="disposition_number_format" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Pola Penomoran <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="disposition_number_format" 
                        name="disposition_number_format" 
                        value="{{ old('disposition_number_format', $settings['disposition_number_format']) }}" 
                        required 
                        placeholder="JMM/{YYYY}/{NUMBER}"
                        oninput="updateNumberPreview()"
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Gunakan tag: <code>{YYYY}</code> untuk tahun, <code>{NUMBER}</code> untuk nomor urut.</p>
                </div>

                <div>
                    <label for="disposition_number_digits" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Jumlah Digit Nomor Urut <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        min="3" 
                        max="8" 
                        id="disposition_number_digits" 
                        name="disposition_number_digits" 
                        value="{{ old('disposition_number_digits', $settings['disposition_number_digits']) }}" 
                        required 
                        oninput="updateNumberPreview()"
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Contoh: 4 digit akan menghasilkan 0001, 0002, dst.</p>
                </div>
            </div>

            <!-- Preview Nomor Disposisi -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Simulasi Nomor Yang Dihasilkan:</span>
                    <span id="number-preview" class="font-mono font-extrabold text-primary-700 text-sm mt-0.5 block">
                        JMM/{{ date('Y') }}/0001
                    </span>
                </div>
                <div class="text-[11px] text-slate-500 text-right">
                    Nomor urut dikunci otomatis oleh sistem agar tidak terjadi nomor ganda.
                </div>
            </div>
        </div>

        <!-- Tombol Simpan -->
        <div class="flex items-center justify-end gap-3">
            <button 
                type="submit" 
                class="px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition flex items-center gap-2 shadow-md shadow-primary-600/20"
            >
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Simpan Perubahan Pengaturan</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function updateNumberPreview() {
        const format = document.getElementById('disposition_number_format').value || 'JMM/{YYYY}/{NUMBER}';
        const digits = parseInt(document.getElementById('disposition_number_digits').value) || 4;
        const year = new Date().getFullYear();
        const paddedNum = "1".padStart(digits, '0');

        const preview = format.replace('{YYYY}', year).replace('{NUMBER}', paddedNum);
        document.getElementById('number-preview').innerText = preview;
    }
</script>
@endpush

