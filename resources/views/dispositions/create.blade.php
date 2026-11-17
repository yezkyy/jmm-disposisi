@extends('layouts.app')

@section('title', 'Buat Pengajuan Disposisi')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="file-plus" class="w-6 h-6 text-primary-600"></i>
                Buat Pengajuan Disposisi Pengeluaran
            </h1>
            <p class="text-xs text-slate-500 mt-1">Isi formulir pengajuan, rincian biaya, penerima pembayaran, dan lampiran pendukung.</p>
        </div>
        <a href="{{ route('dispositions.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali</span>
        </a>
    </div>

    <!-- Form Container -->
    <form id="disposition-form" method="POST" action="{{ route('dispositions.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <input type="hidden" name="action" id="form-action" value="submit">

        <!-- 1. Header Informasi Utama -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-5">
            <h2 class="text-sm font-bold text-slate-800 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="info" class="w-4 h-4 text-primary-600"></i>
                1. Informasi Pokok Pengajuan
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Tanggal Disposisi -->
                <div>
                    <label for="disposition_date" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Tanggal Pengajuan <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="disposition_date" 
                        name="disposition_date" 
                        value="{{ old('disposition_date', date('Y-m-d')) }}" 
                        required 
                        placeholder="Pilih tanggal pengajuan..."
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white"
                    >
                    @error('disposition_date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Divisi -->
                <div>
                    <label for="division_id" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Divisi Pemohon
                    </label>
                    <select id="division_id" name="division_id" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                        <option value="">-- Tanpa Divisi Khusus --</option>
                        @foreach ($divisions as $div)
                            <option value="{{ $div->id }}" {{ old('division_id', auth()->user()->division_id) == $div->id ? 'selected' : '' }}>
                                {{ $div->name }} ({{ $div->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Pemohon (Otomatis) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                        Pemohon Disposisi
                    </label>
                    <input 
                        type="text" 
                        value="{{ auth()->user()->name }} ({{ auth()->user()->role?->display_name }})" 
                        disabled 
                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50 text-slate-600 font-medium cursor-not-allowed"
                    >
                </div>
            </div>

            <!-- Perihal -->
            <div>
                <label for="subject" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Perihal Pengeluaran <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="subject" 
                    name="subject" 
                    value="{{ old('subject') }}" 
                    required 
                    placeholder="Contoh: Pengadaan Solar Armada Balikpapan - Samarinda Rute Logistik"
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 font-medium"
                >
                @error('subject') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Deskripsi / Catatan Tambahan -->
            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wider">
                    Keterangan / Latar Belakang Kebutuhan
                </label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="2" 
                    placeholder="Jelaskan kebutuhan operasional atau referensi pekerjaan terkait..."
                    class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >{{ old('description') }}</textarea>
            </div>
        </div>

        <!-- 2. Rincian Pengeluaran (Items: Uraian Kebutuhan, Qty, Satuan, Subtotal, Nota) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="list-ordered" class="w-4 h-4 text-primary-600"></i>
                        2. Rincian Pengeluaran (Items)
                    </h2>
                    <p class="text-[11px] text-slate-500">Input uraian kebutuhan secara langsung, tentukan Qty & Satuan, serta unggah nota/bukti transaksi per baris kebutuhan.</p>
                </div>
                <button type="button" onclick="addItemRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 text-xs font-semibold border border-primary-200 transition">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Tambah Baris</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse" id="items-table">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px] uppercase tracking-wider">
                            <th class="py-2.5 px-2 text-center w-8">#</th>
                            <th class="py-2.5 px-3">Uraian Kebutuhan <span class="text-rose-500">*</span></th>
                            <th class="py-2.5 px-2 text-right w-20">Qty <span class="text-rose-500">*</span></th>
                            <th class="py-2.5 px-2 text-center w-24">Satuan <span class="text-rose-500">*</span></th>
                            <th class="py-2.5 px-3 text-right w-36">Subtotal (Rp) <span class="text-rose-500">*</span></th>
                            <th class="py-2.5 px-3 w-52">Upload Nota / Bukti</th>
                            <th class="py-2.5 px-2 text-center w-10">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="items-container" class="divide-y divide-slate-100">
                        <!-- Default 1 Row -->
                        <tr class="item-row" data-index="0">
                            <td class="py-2.5 px-2 text-center text-slate-400 font-semibold row-number align-middle">1</td>
                            <td class="py-2.5 px-3 align-top">
                                <input type="text" name="items[0][description]" required placeholder="Contoh: Pembelian Oli Mesin, Sparepart, ATK..." class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 font-medium">
                            </td>
                            <td class="py-2.5 px-2 align-top">
                                <input type="number" step="any" min="0.01" name="items[0][qty]" value="1" required class="w-full px-2 py-1.5 text-xs text-right rounded-lg border border-slate-200 font-mono focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </td>
                            <td class="py-2.5 px-2 align-top">
                                <input type="text" name="items[0][unit]" value="Pcs" placeholder="Pcs, unit, liter..." required class="w-full px-2 py-1.5 text-xs text-center rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </td>
                            <td class="py-2.5 px-3 align-top">
                                <input type="number" step="any" name="items[0][amount]" required min="0" placeholder="0" oninput="calculateTotal()" class="item-amount w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 text-right font-mono font-semibold">
                            </td>
                            <td class="py-2.5 px-3 align-top">
                                <div class="space-y-1">
                                    <input type="file" name="items[0][receipt]" accept=".pdf,.jpg,.jpeg,.png" onchange="previewItemFile(this)" class="block w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-lg">
                                    <span class="file-chosen-name text-[10px] text-emerald-600 font-semibold block truncate hidden"></span>
                                </div>
                            </td>
                            <td class="py-2.5 px-2 text-center align-middle">
                                <button type="button" onclick="removeItemRow(this)" class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus baris">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50/50 border-t border-slate-200 text-slate-700 font-semibold text-xs">
                            <td colspan="4" class="py-2 px-3 text-right uppercase tracking-wider text-[11px]">
                                Subtotal Rincian Kebutuhan:
                            </td>
                            <td class="py-2 px-3 text-right font-mono font-bold" id="subtotal-items-display">
                                Rp 0
                            </td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="bg-slate-50/50 border-t border-slate-100 text-slate-700 font-semibold text-xs">
                            <td colspan="4" class="py-2 px-3 text-right uppercase tracking-wider text-[11px]">
                                PPN (Opsional, Boleh Kosong):
                            </td>
                            <td class="py-2 px-2 text-right">
                                <input type="number" step="any" min="0" name="ppn" id="input-ppn" value="{{ old('ppn', 0) }}" placeholder="0" oninput="calculateTotal()" class="w-full px-2.5 py-1 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 text-right font-mono font-semibold">
                            </td>
                            <td colspan="2" class="text-[10px] text-slate-400 pl-3 align-middle italic">Diisi jika transaksi dikenakan PPN</td>
                        </tr>
                        <tr class="bg-blue-50/60 font-bold border-t-2 border-slate-200 text-slate-800">
                            <td colspan="4" class="py-3 px-3 text-right uppercase tracking-wider text-xs text-blue-900">
                                Total Pengajuan Disposisi :
                            </td>
                            <td class="py-3 px-3 text-right text-sm font-mono text-primary-700 font-extrabold" id="total-display">
                                Rp 0
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- 3. Daftar Penerima Dana & Rekening (Multi-recipient) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-primary-600"></i>
                        3. Penerima Dana & Rekening Bank
                    </h2>
                    <p class="text-[11px] text-slate-500">Nama Rekening 1 wajib diisi; Nama Rekening 2 bersifat opsional. Mendukung banyak penerima.</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Button Modal Tambah Rekening (Kebutuhan Eksplisit Client) -->
                    <button type="button" onclick="openAddBankModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-semibold border border-emerald-300 transition shadow-sm">
                        <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>+ Tambah Rekening Master</span>
                    </button>
                    <button type="button" onclick="addRecipientRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 text-xs font-semibold border border-primary-200 transition">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                        <span>Tambah Penerima</span>
                    </button>
                </div>
            </div>

            <div id="recipients-container" class="space-y-4">
                <!-- Default 1 Recipient Card -->
                <div class="recipient-card bg-slate-50/70 rounded-xl p-4 border border-slate-200 space-y-3" data-index="0">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <i data-lucide="user" class="w-3.5 h-3.5 text-primary-600"></i>
                            Penerima #1
                        </span>
                        <button type="button" onclick="removeRecipientCard(this)" class="text-slate-400 hover:text-rose-600 text-xs flex items-center gap-1">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span>Hapus Penerima</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Penerima <span class="text-rose-500">*</span></label>
                            <input type="text" name="recipients[0][recipient_name]" required placeholder="Nama orang / vendor..." class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Jenis Penerima <span class="text-rose-500">*</span></label>
                            <select name="recipients[0][recipient_type]" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="employee">Karyawan</option>
                                <option value="driver">Sopir</option>
                                <option value="vendor">Vendor</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Alokasi Nominal (Rp)</label>
                            <input type="number" step="any" name="recipients[0][allocated_amount]" min="0" placeholder="0" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <!-- Rekening 1 (Wajib) -->
                        <div class="bg-white p-3 rounded-lg border border-slate-200">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1 flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Nama Rekening 1 (Wajib) <span class="text-rose-500">*</span>
                            </label>
                            <select name="recipients[0][primary_bank_account_id]" required class="bank-account-select w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">-- Pilih Rekening Terdaftar --</option>
                                @foreach ($bankAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->owner_name }} - {{ $acc->bank_name }} ({{ $acc->account_number }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Rekening 2 (Opsional) -->
                        <div class="bg-white p-3 rounded-lg border border-slate-200">
                            <label class="block text-[11px] font-bold text-slate-700 mb-1 flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                Nama Rekening 2 (Opsional / Boleh Kosong)
                            </label>
                            <select name="recipients[0][secondary_bank_account_id]" class="bank-account-select w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">-- Tidak Ada / Kosongkan --</option>
                                @foreach ($bankAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->owner_name }} - {{ $acc->bank_name }} ({{ $acc->account_number }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Lampiran Bukti / Nota (Attachments) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-3">
            <h2 class="text-sm font-bold text-slate-800 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="paperclip" class="w-4 h-4 text-primary-600"></i>
                4. Lampiran Dokumen Pendukung
            </h2>
            <p class="text-xs text-slate-500">Unggah nota, invoice, estimasi penawaran harga, atau dokumen pendukung lain (Format: PDF, JPG, PNG &bull; Maks. 10 MB per file).</p>
            <div>
                <input 
                    type="file" 
                    name="attachments[]" 
                    multiple 
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 border border-slate-200 rounded-xl cursor-pointer"
                >
            </div>
        </div>

        <!-- Form Action Buttons -->
        <div class="flex items-center justify-between p-4 bg-slate-100/80 rounded-2xl border border-slate-200">
            <a href="{{ route('dispositions.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">
                Batal
            </a>
            <div class="flex items-center gap-3">
                <button type="button" onclick="submitForm('draft')" class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
                    <i data-lucide="save" class="w-4 h-4 text-slate-500"></i>
                    <span>Simpan sebagai Draft</span>
                </button>
                <button type="button" onclick="submitForm('submit')" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold shadow-md shadow-primary-600/30 transition flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Kirim Pengajuan (Submit)</span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ========================================== -->
<!-- MODAL TAMBAH REKENING MASTER (AJAX MODAL)  -->
<!-- ========================================== -->
<div id="add-bank-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="wallet" class="w-4 h-4 text-emerald-600"></i>
                    Tambah Rekening Bank Baru
                </h3>
                <p class="text-[11px] text-slate-500">Rekening langsung tersimpan ke master dan terpilih tanpa me-reload halaman.</p>
            </div>
            <button type="button" onclick="closeAddBankModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="ajax-add-bank-form" class="space-y-3.5">
            <div>
                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Nama Pemilik Rekening <span class="text-rose-500">*</span></label>
                <input type="text" id="modal-owner-name" required placeholder="Contoh: PT Sumber Rejeki / Slamet Riyadi" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Pilih Bank</label>
                    <select id="modal-bank-id" onchange="syncModalBank(this)" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- Bank Terdaftar --</option>
                        @foreach ($banks as $b)
                            <option value="{{ $b->id }}" data-name="{{ $b->name }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Nama Bank <span class="text-rose-500">*</span></label>
                    <input type="text" id="modal-bank-name" required placeholder="Contoh: Bank Mandiri" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Nomor Rekening <span class="text-rose-500">*</span></label>
                    <input type="text" id="modal-account-number" required placeholder="Contoh: 0881234567" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Jenis Pemilik</label>
                    <select id="modal-owner-type" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="employee">Karyawan</option>
                        <option value="driver">Sopir</option>
                        <option value="vendor">Vendor</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                <input type="text" id="modal-notes" placeholder="Keterangan pendukung..." class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeAddBankModal()" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                    Batal
                </button>
                <button type="button" id="save-bank-btn" onclick="saveBankAccountAjax()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan & Pilih Rekening</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let itemIndex = 1;
    let recipientIndex = 1;

    function renumberItemRows() {
        document.querySelectorAll('#items-container .item-row').forEach((row, i) => {
            const num = row.querySelector('.row-number');
            if (num) num.innerText = i + 1;
        });
    }

    function previewItemFile(input) {
        const parent = input.parentElement;
        const label = parent.querySelector('.file-chosen-name');
        if (input.files && input.files[0]) {
            label.innerText = '✓ ' + input.files[0].name;
            label.classList.remove('hidden');
        } else {
            label.classList.add('hidden');
        }
    }

    // Tambah Baris Rincian Item (Uraian, Qty, Satuan, Subtotal, Upload Nota)
    function addItemRow() {
        const container = document.getElementById('items-container');
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.dataset.index = itemIndex;
        tr.innerHTML = `
            <td class="py-2.5 px-2 text-center text-slate-400 font-semibold row-number align-middle">${itemIndex + 1}</td>
            <td class="py-2.5 px-3 align-top">
                <input type="text" name="items[${itemIndex}][description]" required placeholder="Contoh: Pembelian Oli Mesin, Sparepart, ATK..." class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 font-medium">
            </td>
            <td class="py-2.5 px-2 align-top">
                <input type="number" step="any" min="0.01" name="items[${itemIndex}][qty]" value="1" required class="w-full px-2 py-1.5 text-xs text-right rounded-lg border border-slate-200 font-mono focus:outline-none focus:ring-2 focus:ring-primary-500">
            </td>
            <td class="py-2.5 px-2 align-top">
                <input type="text" name="items[${itemIndex}][unit]" value="Pcs" placeholder="Pcs, unit, liter..." required class="w-full px-2 py-1.5 text-xs text-center rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
            </td>
            <td class="py-2.5 px-3 align-top">
                <input type="number" step="any" name="items[${itemIndex}][amount]" required min="0" placeholder="0" oninput="calculateTotal()" class="item-amount w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500 text-right font-mono font-semibold">
            </td>
            <td class="py-2.5 px-3 align-top">
                <div class="space-y-1">
                    <input type="file" name="items[${itemIndex}][receipt]" accept=".pdf,.jpg,.jpeg,.png" onchange="previewItemFile(this)" class="block w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-lg">
                    <span class="file-chosen-name text-[10px] text-emerald-600 font-semibold block truncate hidden"></span>
                </div>
            </td>
            <td class="py-2.5 px-2 text-center align-middle">
                <button type="button" onclick="removeItemRow(this)" class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus baris">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </td>
        `;
        container.appendChild(tr);
        itemIndex++;
        renumberItemRows();
        if (window.refreshLucideIcons) window.refreshLucideIcons();
    }

    function removeItemRow(btn) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length <= 1) {
            Swal.fire({
                title: 'Perhatian',
                text: 'Minimal harus ada satu baris rincian pengeluaran.',
                icon: 'warning',
                confirmButtonColor: '#026ec4'
            });
            return;
        }
        btn.closest('tr').remove();
        renumberItemRows();
        calculateTotal();
    }

    // Kalkulasi Total Nominal Otomatis (Subtotal Items + PPN = Grand Total)
    function calculateTotal() {
        let subtotal = 0;
        document.querySelectorAll('.item-amount').forEach(input => {
            const val = parseFloat(input.value) || 0;
            subtotal += val;
        });
        const ppnInput = document.getElementById('input-ppn');
        const ppn = parseFloat(ppnInput ? ppnInput.value : 0) || 0;
        const grandTotal = subtotal + ppn;

        const subDisplay = document.getElementById('subtotal-items-display');
        if (subDisplay) {
            subDisplay.innerText = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');
        }
        document.getElementById('total-display').innerText = 'Rp ' + Math.round(grandTotal).toLocaleString('id-ID');
    }

    // Tambah Kartu Penerima
    function addRecipientRow() {
        const container = document.getElementById('recipients-container');
        const card = document.createElement('div');
        card.className = 'recipient-card bg-slate-50/70 rounded-xl p-4 border border-slate-200 space-y-3';
        card.dataset.index = recipientIndex;
        card.innerHTML = `
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                    <i data-lucide="user" class="w-3.5 h-3.5 text-primary-600"></i>
                    Penerima #${recipientIndex + 1}
                </span>
                <button type="button" onclick="removeRecipientCard(this)" class="text-slate-400 hover:text-rose-600 text-xs flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Hapus Penerima</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Penerima <span class="text-rose-500">*</span></label>
                    <input type="text" name="recipients[${recipientIndex}][recipient_name]" required placeholder="Nama orang / vendor..." class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Jenis Penerima <span class="text-rose-500">*</span></label>
                    <select name="recipients[${recipientIndex}][recipient_type]" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="employee">Karyawan</option>
                        <option value="driver">Sopir</option>
                        <option value="vendor">Vendor</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Alokasi Nominal (Rp)</label>
                    <input type="number" step="any" name="recipients[${recipientIndex}][allocated_amount]" min="0" placeholder="0" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                <div class="bg-white p-3 rounded-lg border border-slate-200">
                    <label class="block text-[11px] font-bold text-slate-700 mb-1 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Nama Rekening 1 (Wajib) <span class="text-rose-500">*</span>
                    </label>
                    <select name="recipients[${recipientIndex}][primary_bank_account_id]" required class="bank-account-select w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">-- Pilih Rekening Terdaftar --</option>
                        @foreach ($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->owner_name }} - {{ $acc->bank_name }} ({{ $acc->account_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-white p-3 rounded-lg border border-slate-200">
                    <label class="block text-[11px] font-bold text-slate-700 mb-1 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                        Nama Rekening 2 (Opsional / Boleh Kosong)
                    </label>
                    <select name="recipients[${recipientIndex}][secondary_bank_account_id]" class="bank-account-select w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">-- Tidak Ada / Kosongkan --</option>
                        @foreach ($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->owner_name }} - {{ $acc->bank_name }} ({{ $acc->account_number }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        `;
        container.appendChild(card);
        recipientIndex++;
        if (window.refreshLucideIcons) window.refreshLucideIcons();
    }

    function removeRecipientCard(btn) {
        const cards = document.querySelectorAll('.recipient-card');
        if (cards.length <= 1) {
            Swal.fire({
                title: 'Perhatian',
                text: 'Minimal harus ada satu data penerima dana.',
                icon: 'warning',
                confirmButtonColor: '#026ec4'
            });
            return;
        }
        btn.closest('.recipient-card').remove();
    }

    // Submit handler
    function submitForm(actionType) {
        document.getElementById('form-action').value = actionType;
        const form = document.getElementById('disposition-form');
        if (actionType === 'submit') {
            if (!form.reportValidity()) return;
            Swal.fire({
                title: 'Kirim Pengajuan Disposisi?',
                text: 'Pengajuan akan dikirim langsung ke antrean persetujuan Manager.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim Pengajuan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#026ec4',
                cancelButtonColor: '#64748b',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2',
                    cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2'
                }
            }).then((res) => {
                if (res.isConfirmed) form.submit();
            });
        } else {
            form.submit();
        }
    }

    // ==========================================
    // MODAL TAMBAH REKENING VIA AJAX
    // ==========================================
    function openAddBankModal() {
        document.getElementById('add-bank-modal').classList.remove('hidden');
    }

    function closeAddBankModal() {
        document.getElementById('add-bank-modal').classList.add('hidden');
        document.getElementById('ajax-add-bank-form').reset();
    }

    function syncModalBank(select) {
        const opt = select.options[select.selectedIndex];
        if (opt && opt.dataset.name) {
            document.getElementById('modal-bank-name').value = opt.dataset.name;
        }
    }

    function saveBankAccountAjax() {
        const ownerName = document.getElementById('modal-owner-name').value.trim();
        const bankId = document.getElementById('modal-bank-id').value;
        const bankName = document.getElementById('modal-bank-name').value.trim();
        const accountNumber = document.getElementById('modal-account-number').value.trim();
        const ownerType = document.getElementById('modal-owner-type').value;
        const notes = document.getElementById('modal-notes').value.trim();

        if (!ownerName || !bankName || !accountNumber) {
            Swal.fire({
                title: 'Form Belum Lengkap',
                text: 'Nama pemilik rekening, nama bank, dan nomor rekening wajib diisi.',
                icon: 'warning',
                confirmButtonColor: '#026ec4'
            });
            return;
        }

        const btn = document.getElementById('save-bank-btn');
        btn.disabled = true;
        btn.innerText = 'Menyimpan...';

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch("{{ route('master.bank-accounts.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                owner_name: ownerName,
                bank_id: bankId || null,
                bank_name: bankName,
                account_number: accountNumber,
                owner_type: ownerType,
                notes: notes,
                is_active: 1
            })
        })
        .then(res => res.json())
        .then(result => {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="check" class="w-4 h-4"></i><span>Simpan & Pilih Rekening</span>`;
            if (window.refreshLucideIcons) window.refreshLucideIcons();

            if (result.success && result.data) {
                const acc = result.data;
                const optText = `${acc.owner_name} - ${acc.bank_name} (${acc.account_number})`;

                // Perbarui seluruh dropdown rekening yang ada di form
                document.querySelectorAll('.bank-account-select').forEach(select => {
                    const newOpt = new Option(optText, acc.id, false, false);
                    select.add(newOpt);
                    if (select.tomselect) {
                        select.tomselect.addOption({ value: acc.id, text: optText });
                        select.tomselect.refreshOptions(false);
                    }
                });

                // Pilih otomatis pada penerima pertama jika belum terisi
                const firstSelect = document.querySelector('select[name="recipients[0][primary_bank_account_id]"]');
                if (firstSelect) {
                    firstSelect.value = acc.id;
                    if (firstSelect.tomselect) {
                        firstSelect.tomselect.setValue(acc.id);
                    }
                }

                closeAddBankModal();

                if (window.showToast) {
                    window.showToast('success', 'Rekening baru berhasil disimpan dan ditambahkan ke pilihan formulir!');
                }
            } else {
                Swal.fire('Gagal Menyimpan', result.message || 'Terjadi kesalahan.', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = 'Simpan & Pilih Rekening';
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        });
    }
</script>
@endpush

