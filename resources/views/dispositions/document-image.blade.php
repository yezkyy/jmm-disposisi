<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disposisi {{ $disposition->disposition_number ?: 'Draft' }} - Format Gambar JPG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }
        #document-card {
            width: 820px;
            min-height: 1100px;
            background: #ffffff;
            color: #0f172a;
            box-sizing: border-box;
        }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #ffffff; padding: 0; }
            #document-card {
                width: 100%;
                box-shadow: none !important;
                border: none !important;
                padding: 10mm 15mm;
            }
        }
    </style>
</head>
<body class="min-h-screen py-8 px-4 flex flex-col items-center">

    <!-- Top Action Toolbar (Floating Header) -->
    <div class="no-print fixed top-0 left-0 right-0 z-50 bg-slate-900/90 backdrop-blur border-b border-slate-700/80 px-4 py-3 shadow-xl">
        <div class="max-w-5xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('dispositions.show', $disposition) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition border border-slate-700">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali ke Detail</span>
                </a>
                <div class="hidden sm:block">
                    <span class="text-xs font-bold text-white">{{ $disposition->disposition_number ?: 'Draft #' . $disposition->id }}</span>
                    <span class="text-[11px] text-slate-400 block">Format Gambar JPG (Siap Kirim WhatsApp ke Owner)</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Tombol Salin Gambar (Copy to Clipboard) -->
                <button type="button" id="btn-copy" onclick="copyImageToClipboard()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 text-xs font-bold transition border border-emerald-500/30 shadow-sm" title="Salin gambar langsung ke clipboard untuk ditempel (Ctrl+V) di WhatsApp">
                    <i class="fa-regular fa-copy"></i>
                    <span>Salin Gambar (WA)</span>
                </button>

                <!-- Tombol Unduh JPG -->
                <button type="button" id="btn-download" onclick="downloadAsJpg()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-bold transition shadow-lg shadow-blue-500/20" style="background-color: #026ec4;">
                    <i class="fa-solid fa-file-image"></i>
                    <span>Unduh Gambar (JPG)</span>
                </button>

                <!-- Tombol Cetak Browser -->
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition border border-slate-700">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Spacer for fixed top bar -->
    <div class="no-print h-16 w-full"></div>

    <!-- DOCUMENT CARD CONTAINER (Render target for html2canvas) -->
    <div class="w-full flex justify-center py-4">
        <div id="document-card" class="p-10 shadow-2xl rounded-sm border border-slate-200">
            
            <!-- 1. KOP SURAT PERUSAHAAN -->
            <div class="flex items-center gap-4 border-b-2 border-slate-900 pb-4 mb-4">
                @if ($logoBase64)
                    <div class="w-16 h-16 flex-shrink-0 flex items-center justify-center">
                        <img src="{{ $logoBase64 }}" class="max-w-full max-h-full object-contain" alt="Logo">
                    </div>
                @else
                    <div class="w-14 h-14 flex-shrink-0 rounded-xl bg-blue-700 text-white flex items-center justify-center font-black text-xl">
                        JMM
                    </div>
                @endif
                <div class="flex-1">
                    <h1 class="text-lg font-black text-slate-900 uppercase tracking-wide leading-tight">{{ $companyName }}</h1>
                    <p class="text-[11px] text-slate-600 italic font-medium mt-0.5">{{ $companyTagline }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                        {{ $companyAddress }} &bull; Telp: {{ $companyPhone }} &bull; Email: {{ $companyEmail }}
                    </p>
                </div>
            </div>

            <!-- JUDUL DOKUMEN -->
            <div class="text-center py-2 border-y border-slate-200 my-3 bg-slate-50">
                <h2 class="text-sm font-extrabold text-blue-800 tracking-wider uppercase">
                    FORMULIR DISPOSISI PENGELUARAN DANA
                </h2>
            </div>

            <!-- INFORMASI POKOK METADATA -->
            <div class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-xs mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-start">
                    <span class="w-28 font-bold text-slate-600 text-[11px] uppercase">No. Disposisi</span>
                    <span class="font-bold text-slate-400 mr-2">:</span>
                    <span class="font-mono font-bold text-slate-900 flex-1">{{ $disposition->disposition_number ?: 'DRAFT' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="w-28 font-bold text-slate-600 text-[11px] uppercase">Tanggal</span>
                    <span class="font-bold text-slate-400 mr-2">:</span>
                    <span class="text-slate-800 flex-1">{{ $disposition->disposition_date ? $disposition->disposition_date->format('d/m/Y') : '-' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="w-28 font-bold text-slate-600 text-[11px] uppercase">Pemohon</span>
                    <span class="font-bold text-slate-400 mr-2">:</span>
                    <span class="text-slate-800 flex-1 font-medium">{{ $disposition->applicant?->name ?? '-' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="w-28 font-bold text-slate-600 text-[11px] uppercase">Divisi</span>
                    <span class="font-bold text-slate-400 mr-2">:</span>
                    <span class="text-slate-800 flex-1">{{ $disposition->division?->name ?? 'Operasional' }}</span>
                </div>
                @if (!empty($disposition->invoice_number))
                    <div class="col-span-2 flex items-start pt-0.5">
                        <span class="w-28 font-bold text-slate-600 text-[11px] uppercase flex-shrink-0">No. Invoice</span>
                        <span class="font-bold text-slate-400 mr-2">:</span>
                        <span class="font-mono font-bold text-blue-900 flex-1">{{ $disposition->invoice_number }}</span>
                    </div>
                @endif
                <div class="col-span-2 flex items-start pt-1">
                    <span class="w-28 font-bold text-slate-600 text-[11px] uppercase flex-shrink-0">Perihal</span>
                    <span class="font-bold text-slate-400 mr-2">:</span>
                    <span class="font-bold text-slate-900 flex-1">{{ $disposition->subject }}</span>
                </div>
                @if ($disposition->description)
                    <div class="col-span-2 flex items-start pt-1">
                        <span class="w-28 font-bold text-slate-600 text-[11px] uppercase flex-shrink-0">Keterangan</span>
                        <span class="font-bold text-slate-400 mr-2">:</span>
                        <span class="text-slate-600 flex-1 text-[11px] leading-relaxed whitespace-pre-line">{{ $disposition->description }}</span>
                    </div>
                @endif
            </div>

            <!-- 1. RINCIAN PENGELUARAN (ITEMS) -->
            @php
                $hasItemInvoice = $disposition->items->contains(fn($i) => !empty(trim((string)$i->invoice_number)));
                $hasQtyUnit = $disposition->items->contains(function($i) {
                    $hasQty = !is_null($i->qty) && (float)$i->qty > 0;
                    $unit = trim((string)$i->unit);
                    $hasUnit = !empty($unit) && $unit !== '-';
                    return $hasQty || $hasUnit;
                });
                $totalCols = 2 + ($hasItemInvoice ? 1 : 0) + ($hasQtyUnit ? 2 : 0) + 1; // No + [Invoice] + Uraian + [Qty + Satuan] + Subtotal
                $footerLabelColspan = $totalCols - 1;
            @endphp
            <div class="mb-4">
                <div class="bg-slate-100 border-l-4 border-blue-600 px-3 py-1 text-xs font-bold text-slate-800 uppercase tracking-wide mb-2 flex items-center justify-between">
                    <span>1. Rincian Kebutuhan & Biaya Pengeluaran</span>
                    <span class="text-[10px] text-slate-500 font-normal">{{ $disposition->items->count() }} Uraian</span>
                </div>

                <table class="w-full text-left text-xs border-collapse border border-slate-300">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 font-bold text-[10px] uppercase border-b border-slate-300">
                            <th class="py-2 px-2 text-center border-r border-slate-300 w-10">No</th>
                            @if ($hasItemInvoice)
                                <th class="py-2 px-3 border-r border-slate-300 w-28">No. Invoice</th>
                            @endif
                            <th class="py-2 px-3 border-r border-slate-300">Uraian Kebutuhan</th>
                            @if ($hasQtyUnit)
                                <th class="py-2 px-2 text-right border-r border-slate-300 w-16">Qty</th>
                                <th class="py-2 px-2 text-center border-r border-slate-300 w-20">Satuan</th>
                            @endif
                            <th class="py-2 px-3 text-right w-40">Subtotal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-[11px]">
                        @php $subtotalItems = 0; @endphp
                        @forelse ($disposition->items as $idx => $item)
                            @php $subtotalItems += (float) $item->amount; @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2 px-2 text-center border-r border-slate-200 text-slate-500 font-medium">{{ $idx + 1 }}</td>
                                @if ($hasItemInvoice)
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono text-slate-700 text-[10px]">
                                        {{ $item->invoice_number ?: '-' }}
                                    </td>
                                @endif
                                <td class="py-2 px-3 border-r border-slate-200 font-semibold text-slate-900">
                                    {{ $item->description }}
                                </td>
                                @if ($hasQtyUnit)
                                    <td class="py-2 px-2 text-right border-r border-slate-200 font-mono text-slate-800">
                                        {{ !is_null($item->qty) ? rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') : '-' }}
                                    </td>
                                    <td class="py-2 px-2 text-center border-r border-slate-200 text-slate-600">
                                        {{ $item->unit ?: '-' }}
                                    </td>
                                @endif
                                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">
                                    Rp {{ number_format((float) $item->amount, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $totalCols }}" class="py-4 text-center text-slate-400">Tidak ada rincian pengeluaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-slate-300 text-xs font-bold">
                        <tr class="bg-slate-50">
                            <td colspan="{{ $footerLabelColspan }}" class="py-2 px-3 text-right uppercase text-[10px] text-slate-600 border-r border-slate-300">
                                Subtotal Rincian Kebutuhan:
                            </td>
                            <td class="py-2 px-3 text-right font-mono text-slate-900">
                                Rp {{ number_format($subtotalItems, 2, ',', '.') }}
                            </td>
                        </tr>
                        @if ((float) $disposition->ppn > 0)
                            <tr class="bg-slate-50">
                                <td colspan="{{ $footerLabelColspan }}" class="py-1.5 px-3 text-right uppercase text-[10px] text-slate-600 border-r border-slate-300">
                                    PPN:
                                </td>
                                <td class="py-1.5 px-3 text-right font-mono text-slate-800">
                                    Rp {{ number_format((float) $disposition->ppn, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                        <tr class="bg-blue-50/70 border-t border-slate-300">
                            <td colspan="{{ $footerLabelColspan }}" class="py-2 px-3 text-right uppercase text-[11px] text-blue-900 font-extrabold border-r border-slate-300">
                                Total Pengajuan Disposisi:
                            </td>
                            <td class="py-2 px-3 text-right font-mono font-extrabold text-blue-700 text-sm">
                                Rp {{ number_format((float) $disposition->requested_total, 2, ',', '.') }}
                            </td>
                        </tr>
                        @if ($disposition->final_approved_total && (float)$disposition->final_approved_total != (float)$disposition->requested_total)
                            <tr class="bg-emerald-50 border-t border-emerald-200">
                                <td colspan="{{ $footerLabelColspan }}" class="py-2 px-3 text-right uppercase text-[11px] text-emerald-900 font-extrabold border-r border-emerald-200">
                                    Total Disetujui (ACC Akhir):
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-extrabold text-emerald-800 text-sm">
                                    Rp {{ number_format((float) $disposition->final_approved_total, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>

            <!-- TERBILANG BOX -->
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-700 mb-4 flex items-start gap-2">
                <span class="font-bold uppercase text-[10px] text-slate-500 mt-0.5 flex-shrink-0">Terbilang:</span>
                <span class="italic font-semibold text-slate-800 leading-snug">{{ $terbilang }}</span>
            </div>

            <!-- 2. DATA PENERIMA PEMBAYARAN -->
            <div class="mb-4">
                <div class="bg-slate-100 border-l-4 border-blue-600 px-3 py-1 text-xs font-bold text-slate-800 uppercase tracking-wide mb-2 flex items-center justify-between">
                    <span>2. Rekening Tujuan Transfer (Penerima)</span>
                    <span class="text-[10px] text-slate-500 font-normal">{{ $disposition->recipients->count() }} Penerima</span>
                </div>

                <table class="w-full text-left text-xs border-collapse border border-slate-300">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 font-bold text-[10px] uppercase border-b border-slate-300">
                            <th class="py-2 px-2 text-center border-r border-slate-300 w-10">No</th>
                            <th class="py-2 px-3 border-r border-slate-300">Nama Penerima</th>
                            <th class="py-2 px-3 border-r border-slate-300">Rekening 1 (Wajib)</th>
                            <th class="py-2 px-3 border-r border-slate-300">Rekening 2 (Cadangan)</th>
                            <th class="py-2 px-3 w-44">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-[11px]">
                        @forelse ($disposition->recipients as $idx => $rec)
                            <tr>
                                <td class="py-2 px-2 text-center border-r border-slate-200 text-slate-500">{{ $idx + 1 }}</td>
                                <td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-900">
                                    {{ $rec->recipient_name }}
                                    <span class="block text-[10px] font-normal text-slate-500">{{ $rec->recipient_type_label }}</span>
                                </td>
                                <td class="py-2 px-3 border-r border-slate-200">
                                    <div class="font-mono font-bold text-slate-900">{{ $rec->primary_bank_name }} - {{ $rec->primary_account_number }}</div>
                                    <div class="text-[10px] text-slate-600">a.n. {{ $rec->primary_account_holder }}</div>
                                </td>
                                <td class="py-2 px-3 border-r border-slate-200">
                                    @if ($rec->secondary_account_number)
                                        <div class="font-mono text-slate-800">{{ $rec->secondary_bank_name }} - {{ $rec->secondary_account_number }}</div>
                                        <div class="text-[10px] text-slate-500">a.n. {{ $rec->secondary_account_holder }}</div>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3 text-slate-800 text-[11px]">
                                    {{ $rec->notes ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-3 text-center text-slate-400">Tidak ada data penerima.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- 3. OTORISASI & PERSETUJUAN 2 TAHAP -->
            <div class="mt-5">
                <div class="bg-slate-100 border-l-4 border-blue-600 px-3 py-1 text-xs font-bold text-slate-800 uppercase tracking-wide mb-2">
                    3. Alur Persetujuan & Otorisasi
                </div>

                @php
                    $mgrAppr = $disposition->approvals->where('stage', 'manager')->first();
                    $finAppr = $disposition->approvals->where('stage', 'finance')->first();
                @endphp

                <table class="w-full text-center border-collapse border border-slate-300 text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 font-bold text-[10px] uppercase border-b border-slate-300">
                            <th class="py-2 px-3 border-r border-slate-300 w-1/3">1. Diajukan oleh (Admin/Finance)</th>
                            <th class="py-2 px-3 border-r border-slate-300 w-1/3">2. Persetujuan Manager</th>
                            <th class="py-2 px-3 w-1/3">3. Keputusan Akhir Finance (Sesuai Arahan Owner)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <!-- Pemohon -->
                            <td class="py-3 px-3 border-r border-slate-200 align-top">
                                <div class="text-[10px] text-slate-500 uppercase font-semibold">Pengajuan Admin / Finance</div>
                                <div class="h-16 flex items-center justify-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fa-solid fa-check"></i> Diajukan
                                    </span>
                                </div>
                                <div class="font-bold text-slate-900 underline">{{ $disposition->applicant?->name ?? 'Admin / Finance' }}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $disposition->disposition_date ? $disposition->disposition_date->format('d/m/Y') : '-' }}</div>
                            </td>

                            <!-- Manager -->
                            <td class="py-3 px-3 border-r border-slate-200 align-top">
                                <div class="text-[10px] text-slate-500 uppercase font-semibold">Tahap 1: Manager</div>
                                <div class="h-16 flex items-center justify-center">
                                    @if ($mgrAppr)
                                        @if (in_array($mgrAppr->decision, ['approved', 'partially_approved']))
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-300">
                                                <i class="fa-solid fa-check-circle"></i> ACC MANAGER
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-300">
                                                <i class="fa-solid fa-times-circle"></i> TIDAK ACC
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-[11px] text-amber-600 italic font-semibold">
                                            ( Menunggu Persetujuan Manager )
                                        </span>
                                    @endif
                                </div>
                                <div class="font-bold text-slate-900 {{ $mgrAppr ? 'underline' : 'text-slate-400 font-normal' }}">
                                    {{ $mgrAppr?->actor?->name ?? '-' }}
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">
                                    {{ $mgrAppr ? $mgrAppr->created_at->format('d/m/Y H:i') : '-' }}
                                </div>
                            </td>

                            <!-- Finance / Arahan Owner -->
                            <td class="py-3 px-3 align-top">
                                <div class="text-[10px] text-slate-500 uppercase font-semibold">Tahap 2: Keputusan Akhir Finance</div>
                                <div class="h-16 flex items-center justify-center">
                                    @if ($finAppr && in_array($finAppr->decision, ['approved', 'partially_approved']) && $disposition->approval_status === 'approved')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[11px] font-black bg-emerald-100 text-emerald-800 border-2 border-emerald-400">
                                            <i class="fa-solid fa-circle-check"></i> ACC FINANCE (ARAHAN OWNER)
                                        </span>
                                    @elseif ($finAppr && in_array($finAppr->decision, ['rejected', 'revision_requested']))
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                            <i class="fa-solid fa-times-circle"></i> TIDAK ACC
                                        </span>
                                    @elseif ($disposition->approval_status === 'waiting_finance')
                                        <span class="text-[11px] text-amber-600 italic font-semibold">
                                            ( Menunggu Keputusan Finance )
                                        </span>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic font-medium">
                                            -
                                        </span>
                                    @endif
                                </div>
                                <div class="font-bold text-slate-900 {{ ($finAppr && $disposition->approval_status === 'approved') ? 'underline' : 'text-slate-400 font-normal' }}">
                                    {{ ($finAppr && $disposition->approval_status === 'approved') ? $finAppr->actor?->name : '-' }}
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5">
                                    {{ ($finAppr && $disposition->approval_status === 'approved') ? $finAppr->created_at->format('d/m/Y H:i') : '-' }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- WATERMARK & FOOTER INFO -->
            <div class="mt-6 pt-3 border-t border-slate-200 flex items-center justify-between text-[10px] text-slate-400">
                <div>
                    Dokumen resmi sistem internal PT Jaya Mukti Mandiri &bull; Dibuat secara otomatis
                </div>
                <div>
                    Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WITA
                </div>
            </div>

        </div>
    </div>

    <!-- SCRIPT CAPTURE JPG & CLIPBOARD -->
    <script>
        const safeNumber = '{{ str_replace(["/", " "], ["_", "_"], $disposition->disposition_number ?: "Draft_" . $disposition->id) }}';

        function generateCanvas() {
            const card = document.getElementById('document-card');
            return html2canvas(card, {
                scale: 2.2, // Kualitas tinggi (crisp 300dpi-like image)
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false
            });
        }

        async function downloadAsJpg() {
            const btn = document.getElementById('btn-download');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><span>Membuat JPG...</span>';
            btn.disabled = true;

            try {
                const canvas = await generateCanvas();
                const link = document.createElement('a');
                link.download = `Disposisi_${safeNumber}.jpg`;
                link.href = canvas.toDataURL('image/jpeg', 0.95);
                link.click();

                Swal.fire({
                    icon: 'success',
                    title: 'Gambar Berhasil Diunduh!',
                    text: `File Disposisi_${safeNumber}.jpg telah disimpan ke perangkat Anda. Siap dikirimkan ke WhatsApp Owner.`,
                    confirmButtonColor: '#026ec4'
                });
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Membuat Gambar',
                    text: 'Terjadi kendala saat merender dokumen menjadi format gambar JPG.',
                    confirmButtonColor: '#026ec4'
                });
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function copyImageToClipboard() {
            const btn = document.getElementById('btn-copy');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><span>Menyalin...</span>';
            btn.disabled = true;

            try {
                const canvas = await generateCanvas();
                canvas.toBlob(async (blob) => {
                    try {
                        if (!navigator.clipboard || !window.ClipboardItem) {
                            throw new Error('Clipboard API tidak didukung di peramban ini');
                        }
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob })
                        ]);

                        Swal.fire({
                            icon: 'success',
                            title: 'Gambar Berhasil Disalin!',
                            html: 'Gambar dokumen disposisi telah disalin ke clipboard.<br><br>Silakan buka <strong>WhatsApp Web / Desktop</strong> dan tekan <strong>Ctrl + V</strong> (Paste) pada ruang obrolan Owner.',
                            confirmButtonColor: '#026ec4'
                        });
                    } catch (clipErr) {
                        // Fallback download if clipboard access is denied
                        downloadAsJpg();
                    } finally {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                }, 'image/png');
            } catch (err) {
                console.error(err);
                btn.innerHTML = originalText;
                btn.disabled = false;
                downloadAsJpg();
            }
        }

        // Auto download bila query param ?auto_download=1
        @if (request()->boolean('auto_download'))
            window.addEventListener('load', () => {
                setTimeout(downloadAsJpg, 600);
            });
        @endif
    </script>
</body>
</html>
