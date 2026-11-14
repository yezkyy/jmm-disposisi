@extends('layouts.app')

@section('title', 'Edit Pengajuan Disposisi')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="file-edit" class="w-6 h-6 text-primary-600"></i>
                Edit Pengajuan Disposisi
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Nomor: <span class="font-semibold text-slate-700">{{ $disposition->disposition_number ?: 'Draft #' . $disposition->id }}</span> 
                &bull; Status: <span class="font-semibold uppercase text-amber-600">{{ $disposition->approval_status_label }}</span>
            </p>
        </div>
        <a href="{{ route('dispositions.show', $disposition) }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Detail</span>
        </a>
    </div>

    @if ($disposition->approval_status === \App\Models\Disposition::STATUS_REVISION)
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5"></i>
            <div class="text-xs space-y-1">
                <p class="font-bold">Permintaan Revisi Disposisi</p>
                <p>Pengajuan ini dikembalikan untuk direvisi. Silakan periksa kembali rincian biaya, penerima, atau lampiran sesuai catatan approver, kemudian klik <strong>"Kirim Ulang Pengajuan"</strong> untuk memulai kembali alur approval dari Manager.</p>
            </div>
        </div>
    @endif

    <!-- Form Container -->
    <form id="disposition-form" method="POST" action="{{ route('dispositions.update', $disposition) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" id="form-action" value="draft">

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
                        value="{{ old('disposition_date', $disposition->disposition_date ? $disposition->disposition_date->format('Y-m-d') : date('Y-m-d')) }}" 
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
                            <option value="{{ $div->id }}" {{ old('division_id', $disposition->division_id) == $div->id ? 'selected' : '' }}>
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
                        value="{{ $disposition->applicant?->name ?? auth()->user()->name }}" 
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
                    value="{{ old('subject', $disposition->subject) }}" 
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
                    rows="3" 
                    placeholder="Uraikan alasan pengeluaran, nomor PO/kontrak, atau instruksi khusus bila ada..."
                    class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >{{ old('description', $disposition->description) }}</textarea>
                @error('description') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- 2. Rincian Item Pengeluaran -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-primary-600"></i>
                    2. Rincian Item Pengeluaran <span class="text-rose-500">*</span>
                </h2>
                <button type="button" onclick="addItemRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary-50 text-primary-700 hover:bg-primary-100 text-xs font-semibold transition">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Tambah Baris Item</span>
                </button>
            </div>

            <div class="overflow-x-auto -mx-6 px-6">
                <table class="w-full text-left border-collapse" id="items-table">
                    <thead>
                        <tr class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-2.5 pr-2 w-10 text-center">#</th>
                            <th class="py-2.5 px-2 min-w-[200px]">Deskripsi Item <span class="text-rose-500">*</span></th>
                            <th class="py-2.5 px-2 min-w-[140px]">Kategori</th>
                            <th class="py-2.5 px-2 w-24">Jumlah</th>
                            <th class="py-2.5 px-2 w-24">Satuan</th>
                            <th class="py-2.5 px-2 min-w-[140px]">Harga Satuan (Rp)</th>
                            <th class="py-2.5 px-2 min-w-[140px] text-right">Subtotal (Rp)</th>
                            <th class="py-2.5 pl-2 w-12 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="items-body" class="divide-y divide-slate-100 text-xs">
                        @php
                            $itemsData = old('items', $disposition->items->toArray());
                        @endphp
                        @foreach ($itemsData as $idx => $item)
                            <tr class="item-row group hover:bg-slate-50/50 transition" data-index="{{ $idx }}">
                                <td class="py-2.5 pr-2 text-center text-slate-400 font-semibold row-number">{{ $idx + 1 }}</td>
                                <td class="py-2 px-2">
                                    <input 
                                        type="text" 
                                        name="items[{{ $idx }}][item_name]" 
                                        value="{{ $item['item_name'] ?? '' }}" 
                                        required 
                                        placeholder="Nama barang / jasa"
                                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    >
                                </td>
                                <td class="py-2 px-2">
                                    <select name="items[{{ $idx }}][category_id]" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white">
                                        <option value="">-- Umum --</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ ($item['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 px-2">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0.01" 
                                        name="items[{{ $idx }}][quantity]" 
                                        value="{{ $item['quantity'] ?? 1 }}" 
                                        required 
                                        oninput="calculateItemTotal(this)" 
                                        class="item-qty w-full px-2 py-1.5 text-xs text-right rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    >
                                </td>
                                <td class="py-2 px-2">
                                    <input 
                                        type="text" 
                                        name="items[{{ $idx }}][unit]" 
                                        value="{{ $item['unit'] ?? 'Unit' }}" 
                                        placeholder="Liter/Pcs"
                                        class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    >
                                </td>
                                <td class="py-2 px-2">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        name="items[{{ $idx }}][unit_price]" 
                                        value="{{ $item['unit_price'] ?? 0 }}" 
                                        required 
                                        oninput="calculateItemTotal(this)" 
                                        class="item-price w-full px-2 py-1.5 text-xs text-right rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 font-mono"
                                    >
                                </td>
                                <td class="py-2 px-2 text-right">
                                    <input 
                                        type="hidden" 
                                        name="items[{{ $idx }}][amount]" 
                                        value="{{ $item['amount'] ?? 0 }}" 
                                        class="item-subtotal-raw"
                                    >
                                    <span class="item-subtotal-formatted font-mono font-bold text-slate-700">
                                        Rp {{ number_format((float) ($item['amount'] ?? 0), 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="py-2 pl-2 text-center">
                                    <button type="button" onclick="removeItemRow(this)" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition" title="Hapus Baris">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Total Pengajuan Ringkasan -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <div class="text-xs text-slate-500">
                    Total dihitung otomatis berdasarkan jumlah dikali harga satuan.
                </div>
                <div class="text-right">
                    <span class="text-xs uppercase font-bold text-slate-500 mr-3">Total Pengajuan:</span>
                    <span id="grand-total-display" class="text-lg font-extrabold text-primary-700 font-mono">
                        Rp {{ number_format((float) $disposition->requested_total, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 3. Rincian Penerima Pembayaran -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="user-check" class="w-4 h-4 text-primary-600"></i>
                        3. Rincian Penerima Pembayaran <span class="text-rose-500">*</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Satu pengajuan dapat ditujukan kepada beberapa penerima transfer (contoh: honor beberapa sopir sekaligus).</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="openAddBankModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition" title="Daftarkan rekening baru ke database">
                        <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                        <span>+ Tambah Rekening Master</span>
                    </button>
                    <button type="button" onclick="addRecipientCard()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary-50 text-primary-700 hover:bg-primary-100 text-xs font-semibold transition">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Tambah Penerima</span>
                    </button>
                </div>
            </div>

            <div id="recipients-container" class="space-y-4">
                @php
                    $recipientsData = old('recipients', $disposition->recipients->toArray());
                @endphp
                @foreach ($recipientsData as $rIdx => $rec)
                    <div class="recipient-card p-4 rounded-xl border border-slate-200/90 bg-slate-50/50 space-y-4" data-index="{{ $rIdx }}">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <span class="text-xs font-bold text-slate-700 recipient-title flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-[10px] font-extrabold recipient-number">
                                    {{ $rIdx + 1 }}
                                </span>
                                Penerima Pembayaran #{{ $rIdx + 1 }}
                            </span>
                            <button type="button" onclick="removeRecipientCard(this)" class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1 p-1">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                <span>Hapus Penerima</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Nama Penerima -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1 uppercase tracking-wider">
                                    Nama Penerima <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    name="recipients[{{ $rIdx }}][recipient_name]" 
                                    value="{{ $rec['recipient_name'] ?? '' }}" 
                                    required 
                                    placeholder="Nama sopir, vendor, karyawan, dsb."
                                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white"
                                >
                            </div>

                            <!-- Tipe Penerima -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1 uppercase tracking-wider">
                                    Kategori Penerima
                                </label>
                                <select name="recipients[{{ $rIdx }}][recipient_type]" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white">
                                    <option value="driver" {{ ($rec['recipient_type'] ?? '') == 'driver' ? 'selected' : '' }}>Sopir / Driver</option>
                                    <option value="employee" {{ ($rec['recipient_type'] ?? '') == 'employee' ? 'selected' : '' }}>Karyawan</option>
                                    <option value="vendor" {{ ($rec['recipient_type'] ?? '') == 'vendor' ? 'selected' : '' }}>Vendor / Supplier</option>
                                    <option value="other" {{ ($rec['recipient_type'] ?? '') == 'other' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>

                            <!-- Nominal Dialokasikan -->
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1 uppercase tracking-wider">
                                    Nominal Alokasi (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    name="recipients[{{ $rIdx }}][amount]" 
                                    value="{{ $rec['amount'] ?? 0 }}" 
                                    required 
                                    class="w-full px-3 py-2 text-xs text-right rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 font-mono font-bold bg-white"
                                >
                            </div>
                        </div>

                        <!-- Data Rekening: Wajib Rekening 1, Opsional Rekening 2 -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-200/70">
                            <!-- Rekening Utama 1 (Wajib) -->
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px]">1</span>
                                    Rekening Utama 1 <span class="text-rose-500">*</span>
                                </label>
                                <div>
                                    <select 
                                        name="recipients[{{ $rIdx }}][primary_bank_account_id]" 
                                        required 
                                        onchange="handlePrimaryBankSelect(this, {{ $rIdx }})"
                                        class="bank-account-select w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white"
                                    >
                                        <option value="">-- Pilih dari Master Rekening --</option>
                                        @foreach ($bankAccounts as $acc)
                                            <option 
                                                value="{{ $acc->id }}" 
                                                data-owner="{{ $acc->owner_name }}" 
                                                data-bank="{{ $acc->bank_name }}" 
                                                data-number="{{ $acc->account_number }}"
                                                {{ ($rec['primary_bank_account_id'] ?? '') == $acc->id ? 'selected' : '' }}
                                            >
                                                {{ $acc->owner_name }} - {{ $acc->bank_name }} ({{ $acc->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-3 gap-2">
                                    <input 
                                        type="text" 
                                        name="recipients[{{ $rIdx }}][primary_bank_name]" 
                                        id="rec-{{ $rIdx }}-p-bank" 
                                        value="{{ $rec['primary_bank_name'] ?? '' }}" 
                                        required 
                                        placeholder="Bank" 
                                        class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white"
                                    >
                                    <input 
                                        type="text" 
                                        name="recipients[{{ $rIdx }}][primary_account_number]" 
                                        id="rec-{{ $rIdx }}-p-num" 
                                        value="{{ $rec['primary_account_number'] ?? '' }}" 
                                        required 
                                        placeholder="Nomor Rekening" 
                                        class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 font-mono bg-white"
                                    >
                                    <input 
                                        type="text" 
                                        name="recipients[{{ $rIdx }}][primary_account_holder]" 
                                        id="rec-{{ $rIdx }}-p-holder" 
                                        value="{{ $rec['primary_account_holder'] ?? '' }}" 
                                        required 
                                        placeholder="Nama Pemilik" 
                                        class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white"
                                    >
                                </div>
                            </div>

                            <!-- Rekening Alternatif 2 (Opsional) -->
                            <div class="space-y-2">
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">2</span>
                                    Rekening Alternatif 2 (Opsional)
                                </label>
                                <div>
                                    <select 
                                        name="recipients[{{ $rIdx }}][secondary_bank_account_id]" 
                                        onchange="handleSecondaryBankSelect(this, {{ $rIdx }})"
                                        class="bank-account-select w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white"
                                    >
                                        <option value="">-- Tanpa Rekening Cadangan --</option>
                                        @foreach ($bankAccounts as $acc)
                                            <option 
                                                value="{{ $acc->id }}" 
                                                data-owner="{{ $acc->owner_name }}" 
                                                data-bank="{{ $acc->bank_name }}" 
                                                data-number="{{ $acc->account_number }}"
                                                {{ ($rec['secondary_bank_account_id'] ?? '') == $acc->id ? 'selected' : '' }}
                                            >
                                                {{ $acc->owner_name }} - {{ $acc->bank_name }} ({{ $acc->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-3 gap-2">
                                    <input 
                                        type="text" 
                                        name="recipients[{{ $rIdx }}][secondary_bank_name]" 
                                        id="rec-{{ $rIdx }}-s-bank" 
                                        value="{{ $rec['secondary_bank_name'] ?? '' }}" 
                                        placeholder="Bank (opsional)" 
                                        class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white"
                                    >
                                    <input 
                                        type="text" 
                                        name="recipients[{{ $rIdx }}][secondary_account_number]" 
                                        id="rec-{{ $rIdx }}-s-num" 
                                        value="{{ $rec['secondary_account_number'] ?? '' }}" 
                                        placeholder="No Rek (opsional)" 
                                        class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 font-mono bg-white"
                                    >
                                    <input 
                                        type="text" 
                                        name="recipients[{{ $rIdx }}][secondary_account_holder]" 
                                        id="rec-{{ $rIdx }}-s-holder" 
                                        value="{{ $rec['secondary_account_holder'] ?? '' }}" 
                                        placeholder="Pemilik (opsional)" 
                                        class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Khusus Penerima -->
                        <div>
                            <input 
                                type="text" 
                                name="recipients[{{ $rIdx }}][notes]" 
                                value="{{ $rec['notes'] ?? '' }}" 
                                placeholder="Catatan khusus transfer atau rute supir..."
                                class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white"
                            >
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 4. Lampiran / Dokumen Pendukung -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-800 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i data-lucide="paperclip" class="w-4 h-4 text-primary-600"></i>
                4. Lampiran & Dokumen Pendukung
            </h2>

            @if ($disposition->attachments->count() > 0)
                <div class="mb-4 space-y-2">
                    <p class="text-xs font-semibold text-slate-700">Lampiran Saat Ini:</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($disposition->attachments as $att)
                            <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="group flex items-center justify-between p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-primary-300 hover:shadow-xs transition text-xs cursor-pointer">
                                <div class="flex items-center gap-2 truncate">
                                    <i data-lucide="file" class="w-4 h-4 text-primary-600 flex-shrink-0 group-hover:scale-110 transition-transform"></i>
                                    <span class="text-slate-700 group-hover:text-primary-700 truncate font-medium">
                                        {{ $att->file_name }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-400 group-hover:text-primary-600 flex-shrink-0 flex items-center gap-1">
                                    <span>{{ number_format($att->file_size / 1024, 0) }} KB</span>
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="text-xs text-slate-500">Unggah tambahan berkas PDF, JPG, PNG (maksimal 5MB per file):</p>
            <input 
                type="file" 
                name="attachments[]" 
                multiple 
                accept=".pdf,.png,.jpg,.jpeg" 
                class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer"
            >
        </div>

        <!-- Tombol Aksi -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <button 
                type="button" 
                onclick="submitForm('draft')" 
                class="px-5 py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-bold transition flex items-center gap-2 shadow-sm"
            >
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Simpan Perubahan (Draft)</span>
            </button>
            <button 
                type="button" 
                onclick="submitForm('submit')" 
                class="px-6 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition flex items-center gap-2 shadow-md shadow-primary-600/20"
            >
                <i data-lucide="send" class="w-4 h-4"></i>
                <span>{{ $disposition->approval_status === \App\Models\Disposition::STATUS_REVISION ? 'Kirim Ulang Pengajuan' : 'Kirim ke Approval Manager' }}</span>
            </button>
        </div>
    </form>
</div>

<!-- Modal Tambah Master Rekening (In-place Modal) -->
<div id="add-bank-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="credit-card" class="w-4 h-4 text-primary-600"></i>
                Tambah Rekening Master Baru
            </h3>
            <button type="button" onclick="closeAddBankModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-xs text-slate-500">Rekening ini akan disimpan ke Master Rekening sehingga dapat digunakan kembali pada pengajuan berikutnya.</p>
            
            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1 uppercase tracking-wider">Nama Pemilik Rekening <span class="text-rose-500">*</span></label>
                    <input type="text" id="modal-owner-name" placeholder="Nama pemilik rekening sesuai buku tabungan" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1 uppercase tracking-wider">Pilih Bank <span class="text-rose-500">*</span></label>
                        <select id="modal-bank-id" onchange="handleModalBankSelect(this)" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-white">
                            <option value="">-- Pilih Bank --</option>
                            @foreach ($banks as $b)
                                <option value="{{ $b->id }}" data-name="{{ $b->name }}">{{ $b->name }} ({{ $b->code }})</option>
                            @endforeach
                            <option value="other">-- Bank Lainnya --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1 uppercase tracking-wider">Nama Bank</label>
                        <input type="text" id="modal-bank-name" placeholder="Nama bank" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1 uppercase tracking-wider">Nomor Rekening <span class="text-rose-500">*</span></label>
                    <input type="text" id="modal-account-number" placeholder="Nomor rekening (angka nol depan tidak akan hilang)" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 font-mono focus:outline-none focus:ring-1 focus:ring-primary-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1 uppercase tracking-wider">Jenis Pemilik</label>
                        <select id="modal-owner-type" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-white">
                            <option value="driver">Sopir / Driver</option>
                            <option value="employee">Karyawan</option>
                            <option value="vendor">Vendor / Rekanan</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1 uppercase tracking-wider">Catatan</label>
                        <input type="text" id="modal-notes" placeholder="Opsional" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" onclick="closeAddBankModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition">Batal</button>
            <button type="button" id="save-bank-btn" onclick="saveNewBankAccount()" class="px-4 py-2 text-xs font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-xl transition flex items-center gap-1.5 shadow-sm">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Simpan & Pilih Rekening</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let itemIndex = {{ count($itemsData) }};
    let recipientIndex = {{ count($recipientsData) }};

    function calculateItemTotal(input) {
        const row = input.closest('.item-row');
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const subtotal = qty * price;

        row.querySelector('.item-subtotal-raw').value = subtotal.toFixed(2);
        row.querySelector('.item-subtotal-formatted').innerText = 'Rp ' + subtotal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        updateGrandTotal();
    }

    function updateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.item-subtotal-raw').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('grand-total-display').innerText = 'Rp ' + total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function addItemRow() {
        const body = document.getElementById('items-body');
        const tr = document.createElement('tr');
        tr.className = 'item-row group hover:bg-slate-50/50 transition';
        tr.dataset.index = itemIndex;

        tr.innerHTML = `
            <td class="py-2.5 pr-2 text-center text-slate-400 font-semibold row-number">${body.children.length + 1}</td>
            <td class="py-2 px-2">
                <input type="text" name="items[${itemIndex}][item_name]" required placeholder="Nama barang / jasa" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </td>
            <td class="py-2 px-2">
                <select name="items[${itemIndex}][category_id]" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white">
                    <option value="">-- Umum --</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </td>
            <td class="py-2 px-2">
                <input type="number" step="0.01" min="0.01" name="items[${itemIndex}][quantity]" value="1" required oninput="calculateItemTotal(this)" class="item-qty w-full px-2 py-1.5 text-xs text-right rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </td>
            <td class="py-2 px-2">
                <input type="text" name="items[${itemIndex}][unit]" value="Unit" placeholder="Liter/Pcs" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500">
            </td>
            <td class="py-2 px-2">
                <input type="number" step="0.01" min="0" name="items[${itemIndex}][unit_price]" value="0" required oninput="calculateItemTotal(this)" class="item-price w-full px-2 py-1.5 text-xs text-right rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 font-mono">
            </td>
            <td class="py-2 px-2 text-right">
                <input type="hidden" name="items[${itemIndex}][amount]" value="0" class="item-subtotal-raw">
                <span class="item-subtotal-formatted font-mono font-bold text-slate-700">Rp 0,00</span>
            </td>
            <td class="py-2 pl-2 text-center">
                <button type="button" onclick="removeItemRow(this)" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg transition" title="Hapus Baris">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </td>
        `;

        body.appendChild(tr);
        itemIndex++;
        renumberRows();
        if (window.refreshLucideIcons) window.refreshLucideIcons();
    }

    function removeItemRow(button) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length <= 1) {
            Swal.fire({
                title: 'Perhatian',
                text: 'Disposisi wajib memiliki minimal satu baris item rincian biaya.',
                icon: 'warning',
                confirmButtonColor: '#026ec4'
            });
            return;
        }
        button.closest('.item-row').remove();
        renumberRows();
        updateGrandTotal();
    }

    function renumberRows() {
        document.querySelectorAll('.item-row').forEach((row, i) => {
            row.querySelector('.row-number').innerText = i + 1;
        });
    }

    function addRecipientCard() {
        const container = document.getElementById('recipients-container');
        const card = document.createElement('div');
        card.className = 'recipient-card p-4 rounded-xl border border-slate-200/90 bg-slate-50/50 space-y-4';
        card.dataset.index = recipientIndex;

        card.innerHTML = `
            <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <span class="text-xs font-bold text-slate-700 recipient-title flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-[10px] font-extrabold recipient-number">
                        ${container.children.length + 1}
                    </span>
                    Penerima Pembayaran #${container.children.length + 1}
                </span>
                <button type="button" onclick="removeRecipientCard(this)" class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1 p-1">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Hapus Penerima</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1 uppercase tracking-wider">Nama Penerima <span class="text-rose-500">*</span></label>
                    <input type="text" name="recipients[${recipientIndex}][recipient_name]" required placeholder="Nama sopir, vendor, karyawan, dsb." class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1 uppercase tracking-wider">Kategori Penerima</label>
                    <select name="recipients[${recipientIndex}][recipient_type]" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white">
                        <option value="driver">Sopir / Driver</option>
                        <option value="employee">Karyawan</option>
                        <option value="vendor">Vendor / Supplier</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1 uppercase tracking-wider">Nominal Alokasi (Rp) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="recipients[${recipientIndex}][amount]" value="0" required class="w-full px-3 py-2 text-xs text-right rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 font-mono font-bold bg-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-200/70">
                <div class="space-y-2">
                    <label class="block text-[11px] font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px]">1</span>
                        Rekening Utama 1 <span class="text-rose-500">*</span>
                    </label>
                    <select name="recipients[${recipientIndex}][primary_bank_account_id]" required onchange="handlePrimaryBankSelect(this, ${recipientIndex})" class="bank-account-select w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white">
                        <option value="">-- Pilih dari Master Rekening --</option>
                        @foreach ($bankAccounts as $acc)
                            <option value="{{ $acc->id }}" data-owner="{{ $acc->owner_name }}" data-bank="{{ $acc->bank_name }}" data-number="{{ $acc->account_number }}">
                                {{ $acc->owner_name }} - {{ $acc->bank_name }} ({{ $acc->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="text" name="recipients[${recipientIndex}][primary_bank_name]" id="rec-${recipientIndex}-p-bank" required placeholder="Bank" class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white">
                        <input type="text" name="recipients[${recipientIndex}][primary_account_number]" id="rec-${recipientIndex}-p-num" required placeholder="Nomor Rekening" class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 font-mono bg-white">
                        <input type="text" name="recipients[${recipientIndex}][primary_account_holder]" id="rec-${recipientIndex}-p-holder" required placeholder="Nama Pemilik" class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-[10px]">2</span>
                        Rekening Alternatif 2 (Opsional)
                    </label>
                    <select name="recipients[${recipientIndex}][secondary_bank_account_id]" onchange="handleSecondaryBankSelect(this, ${recipientIndex})" class="bank-account-select w-full px-3 py-2 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white">
                        <option value="">-- Tanpa Rekening Cadangan --</option>
                        @foreach ($bankAccounts as $acc)
                            <option value="{{ $acc->id }}" data-owner="{{ $acc->owner_name }}" data-bank="{{ $acc->bank_name }}" data-number="{{ $acc->account_number }}">
                                {{ $acc->owner_name }} - {{ $acc->bank_name }} ({{ $acc->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="text" name="recipients[${recipientIndex}][secondary_bank_name]" id="rec-${recipientIndex}-s-bank" placeholder="Bank (opsional)" class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white">
                        <input type="text" name="recipients[${recipientIndex}][secondary_account_number]" id="rec-${recipientIndex}-s-num" placeholder="No Rek (opsional)" class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 font-mono bg-white">
                        <input type="text" name="recipients[${recipientIndex}][secondary_account_holder]" id="rec-${recipientIndex}-s-holder" placeholder="Pemilik (opsional)" class="px-2.5 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white">
                    </div>
                </div>
            </div>

            <div>
                <input type="text" name="recipients[${recipientIndex}][notes]" placeholder="Catatan khusus transfer atau rute supir..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:ring-1 focus:ring-primary-500 bg-white">
            </div>
        `;

        container.appendChild(card);
        recipientIndex++;
        renumberRecipients();
        if (window.refreshLucideIcons) window.refreshLucideIcons();
    }

    function removeRecipientCard(button) {
        const cards = document.querySelectorAll('.recipient-card');
        if (cards.length <= 1) {
            Swal.fire({
                title: 'Perhatian',
                text: 'Pengajuan harus memiliki minimal satu penerima pembayaran.',
                icon: 'warning',
                confirmButtonColor: '#026ec4'
            });
            return;
        }
        button.closest('.recipient-card').remove();
        renumberRecipients();
    }

    function renumberRecipients() {
        document.querySelectorAll('.recipient-card').forEach((card, idx) => {
            card.querySelector('.recipient-number').innerText = idx + 1;
            card.querySelector('.recipient-title').childNodes[2].nodeValue = ` Penerima Pembayaran #${idx + 1}`;
        });
    }

    function handlePrimaryBankSelect(select, rIdx) {
        const opt = select.options[select.selectedIndex];
        if (opt && opt.value) {
            document.getElementById(`rec-${rIdx}-p-bank`).value = opt.dataset.bank || '';
            document.getElementById(`rec-${rIdx}-p-num`).value = opt.dataset.number || '';
            document.getElementById(`rec-${rIdx}-p-holder`).value = opt.dataset.owner || '';
        }
    }

    function handleSecondaryBankSelect(select, rIdx) {
        const opt = select.options[select.selectedIndex];
        if (opt && opt.value) {
            document.getElementById(`rec-${rIdx}-s-bank`).value = opt.dataset.bank || '';
            document.getElementById(`rec-${rIdx}-s-num`).value = opt.dataset.number || '';
            document.getElementById(`rec-${rIdx}-s-holder`).value = opt.dataset.owner || '';
        } else {
            document.getElementById(`rec-${rIdx}-s-bank`).value = '';
            document.getElementById(`rec-${rIdx}-s-num`).value = '';
            document.getElementById(`rec-${rIdx}-s-holder`).value = '';
        }
    }

    function submitForm(actionType) {
        document.getElementById('form-action').value = actionType;
        const msg = actionType === 'submit' 
            ? 'Pengajuan akan dikirim ke antrean approval Manager. Pastikan seluruh rincian dan nomor rekening sudah benar.'
            : 'Perubahan draft akan disimpan dan dapat diedit kembali kapan saja.';

        Swal.fire({
            title: actionType === 'submit' ? 'Kirim Pengajuan Disposisi?' : 'Simpan Draft?',
            text: msg,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: actionType === 'submit' ? 'Ya, Kirim Sekarang' : 'Ya, Simpan Draft',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#026ec4'
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('disposition-form').submit();
            }
        });
    }

    // Modal In-Place Master Rekening
    function openAddBankModal() {
        document.getElementById('add-bank-modal').classList.remove('hidden');
        document.getElementById('add-bank-modal').classList.add('flex');
    }

    function closeAddBankModal() {
        document.getElementById('add-bank-modal').classList.remove('flex');
        document.getElementById('add-bank-modal').classList.add('hidden');
        document.getElementById('modal-owner-name').value = '';
        document.getElementById('modal-bank-id').value = '';
        document.getElementById('modal-bank-name').value = '';
        document.getElementById('modal-account-number').value = '';
        document.getElementById('modal-notes').value = '';
    }

    function handleModalBankSelect(select) {
        const opt = select.options[select.selectedIndex];
        if (opt.value === 'other') {
            document.getElementById('modal-bank-name').value = '';
            document.getElementById('modal-bank-name').focus();
        } else if (opt.value) {
            document.getElementById('modal-bank-name').value = opt.dataset.name || '';
        }
    }

    function saveNewBankAccount() {
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

                document.querySelectorAll('.bank-account-select').forEach(select => {
                    const newOpt = new Option(optText, acc.id, false, false);
                    select.add(newOpt);
                    if (select.tomselect) {
                        select.tomselect.addOption({ value: acc.id, text: optText });
                        select.tomselect.refreshOptions(false);
                    }
                });

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

