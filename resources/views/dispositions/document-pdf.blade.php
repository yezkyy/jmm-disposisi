<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Disposisi {{ $disposition->disposition_number ?: 'Draft' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .logo-cell {
            width: 70px;
            vertical-align: middle;
        }
        .logo-img {
            max-width: 65px;
            max-height: 65px;
        }
        .company-cell {
            vertical-align: middle;
            padding-left: 10px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }
        .company-tagline {
            font-size: 7.5pt;
            color: #475569;
            margin: 1px 0;
            font-style: italic;
        }
        .company-contact {
            font-size: 7pt;
            color: #64748b;
            margin: 0;
        }
        .doc-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 10px 0 14px 0;
            color: #026ec4;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 4px 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 4px;
            vertical-align: top;
            font-size: 8.5pt;
        }
        .meta-label {
            width: 18%;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            font-size: 7.5pt;
        }
        .meta-colon {
            width: 2%;
            font-weight: bold;
        }
        .meta-value {
            width: 30%;
            color: #0f172a;
        }
        .section-header {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            background-color: #f1f5f9;
            padding: 4px 6px;
            margin-top: 10px;
            margin-bottom: 6px;
            border-left: 3px solid #026ec4;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .content-table th {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #334155;
            text-align: left;
        }
        .content-table td {
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            font-size: 8pt;
            vertical-align: top;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier', monospace; font-size: 8pt; }
        .font-bold { font-weight: bold; }
        
        .box-terbilang {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 6px 8px;
            margin: 8px 0 12px 0;
            font-size: 8pt;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .signatures-table td {
            width: 33.33%;
            border: 1px solid #cbd5e1;
            padding: 6px;
            text-align: center;
            vertical-align: top;
            font-size: 7.5pt;
        }
        .sig-role {
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            height: 24px;
        }
        .sig-space {
            height: 48px;
        }
        .sig-name {
            font-weight: bold;
            color: #0f172a;
            text-decoration: underline;
        }
        .sig-date {
            color: #64748b;
            font-size: 7pt;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <!-- Kop Surat Perusahaan -->
    <table class="header-table">
        <tr>
            @if ($logoBase64)
                <td class="logo-cell">
                    <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                </td>
            @endif
            <td class="company-cell">
                <h1 class="company-name">{{ $companyName }}</h1>
                <p class="company-tagline">{{ $companyTagline }}</p>
                <p class="company-contact">{{ $companyAddress }} &bull; Telp: {{ $companyPhone }} &bull; Email: {{ $companyEmail }}</p>
            </td>
        </tr>
    </table>

    <!-- Judul Dokumen -->
    <div class="doc-title">
        FORMULIR DISPOSISI PENGELUARAN DANA
    </div>

    <!-- Informasi Pokok Metadata -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">No. Disposisi</td>
            <td class="meta-colon">:</td>
            <td class="meta-value font-mono font-bold">{{ $disposition->disposition_number ?: 'DRAFT' }}</td>

            <td class="meta-label">Tanggal</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ $disposition->disposition_date ? $disposition->disposition_date->format('d/m/Y') : '-' }}</td>
        </tr>
        @if (!empty($disposition->invoice_number))
            <tr>
                <td class="meta-label">No. Invoice</td>
                <td class="meta-colon">:</td>
                <td colspan="4" class="meta-value font-mono font-bold" style="color: #026ec4;">{{ $disposition->invoice_number }}</td>
            </tr>
        @endif
        <tr>
            <td class="meta-label">Pemohon</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ $disposition->applicant?->name }}</td>

            <td class="meta-label">Divisi</td>
            <td class="meta-colon">:</td>
            <td class="meta-value">{{ $disposition->division?->name ?? 'Operasional' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Perihal</td>
            <td class="meta-colon">:</td>
            <td colspan="4" class="meta-value font-bold">{{ $disposition->subject }}</td>
        </tr>
        @if ($disposition->description)
            <tr>
                <td class="meta-label">Keterangan</td>
                <td class="meta-colon">:</td>
                <td colspan="4" class="meta-value" style="font-size: 7.5pt; color: #475569;">{{ $disposition->description }}</td>
            </tr>
        @endif
    </table>

    <!-- 1. Rincian Anggaran Item Pengeluaran -->
    @php
        $hasItemInvoice = $disposition->items->contains(fn($i) => !empty(trim((string)$i->invoice_number)));
        $hasQtyUnit = $disposition->items->contains(function($i) {
            $hasQty = !is_null($i->qty) && (float)$i->qty > 0;
            $unit = trim((string)$i->unit);
            $hasUnit = !empty($unit) && $unit !== '-';
            return $hasQty || $hasUnit;
        });
        $pdfCols = 2 + ($hasItemInvoice ? 1 : 0) + ($hasQtyUnit ? 2 : 0) + 1;
        $pdfFooterColspan = $pdfCols - 1;
    @endphp
    <div class="section-header">1. Rincian Kebutuhan & Biaya Pengeluaran</div>
    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                @if ($hasItemInvoice)
                    <th style="width: 18%;">No. Invoice</th>
                @endif
                <th style="{{ $hasQtyUnit ? ($hasItemInvoice ? 'width: 42%;' : 'width: 50%;') : ($hasItemInvoice ? 'width: 57%;' : 'width: 75%;') }}">Uraian Kebutuhan</th>
                @if ($hasQtyUnit)
                    <th style="width: 10%;" class="text-right">Qty</th>
                    <th style="width: 10%;" class="text-center">Satuan</th>
                @endif
                <th style="width: 20%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $subtotalItems = 0; @endphp
            @forelse ($disposition->items as $idx => $item)
                @php $subtotalItems += (float) $item->amount; @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    @if ($hasItemInvoice)
                        <td class="font-mono" style="font-size: 7.5pt;">{{ $item->invoice_number ?: '-' }}</td>
                    @endif
                    <td><strong>{!! nl2br(e($item->description)) !!}</strong></td>
                    @if ($hasQtyUnit)
                        <td class="text-right font-mono">{{ $item->formatted_qty ?? '-' }}</td>
                        <td class="text-center">{{ $item->unit ?: '-' }}</td>
                    @endif
                    <td class="text-right font-mono font-bold">Rp {{ number_format((float) $item->amount, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $pdfCols }}" class="text-center" style="color: #94a3b8; padding: 12px;">Tidak ada rincian pengeluaran.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="{{ $pdfFooterColspan }}" class="text-right" style="padding: 5px 6px; text-transform: uppercase;">Subtotal Rincian Kebutuhan:</td>
                <td class="text-right font-mono font-bold" style="padding: 5px 6px;">
                    Rp {{ number_format($subtotalItems, 2, ',', '.') }}
                </td>
            </tr>
            @if ((float) $disposition->ppn > 0)
                <tr style="background-color: #f8fafc; font-weight: bold;">
                    <td colspan="{{ $pdfFooterColspan }}" class="text-right" style="padding: 5px 6px; text-transform: uppercase;">PPN:</td>
                    <td class="text-right font-mono font-bold" style="padding: 5px 6px;">
                        Rp {{ number_format((float) $disposition->ppn, 2, ',', '.') }}
                    </td>
                </tr>
            @endif
            <tr style="background-color: #f0f9ff; font-weight: bold;">
                <td colspan="{{ $pdfFooterColspan }}" class="text-right" style="padding: 5px 6px; text-transform: uppercase; color: #026ec4;">Total Nominal Pengajuan:</td>
                <td class="text-right font-mono font-bold" style="padding: 5px 6px; color: #026ec4;">
                    Rp {{ number_format((float) $disposition->requested_total, 2, ',', '.') }}
                </td>
            </tr>
            @if ($disposition->final_approved_total && (float)$disposition->final_approved_total != (float)$disposition->requested_total)
                <tr style="background-color: #f0fdf4; font-weight: bold;">
                    <td colspan="{{ $pdfFooterColspan }}" class="text-right" style="padding: 5px 6px; text-transform: uppercase; color: #166534;">Total Nominal Disetujui (ACC Akhir):</td>
                    <td class="text-right font-mono font-bold" style="padding: 5px 6px; color: #166534;">
                        Rp {{ number_format((float) $disposition->final_approved_total, 2, ',', '.') }}
                    </td>
                </tr>
            @endif
        </tfoot>
    </table>

    <!-- Terbilang Box -->
    <div class="box-terbilang">
        <strong>Terbilang:</strong> <em>{{ $terbilang }}</em>
    </div>

    <!-- 2. Rincian Penerima Pembayaran -->
    <div class="section-header">2. Data Penerima Pembayaran (Tujuan Transfer)</div>
    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 25%;">Nama Penerima</th>
                <th style="width: 35%;">Rekening Utama 1 (Wajib)</th>
                <th style="width: 20%;">Rekening Cadangan 2</th>
                <th style="width: 15%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($disposition->recipients as $idx => $rec)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $rec->recipient_name }}</strong>
                        <div style="font-size: 7pt; color: #64748b;">{{ $rec->recipient_type_label }}</div>
                    </td>
                    <td>
                        <div class="font-mono font-bold">{{ $rec->primary_bank_name }} - {{ $rec->primary_account_number }}</div>
                        <div style="font-size: 7pt; color: #475569;">a.n. {{ $rec->primary_account_holder }}</div>
                    </td>
                    <td>
                        @if ($rec->secondary_account_number)
                            <div class="font-mono">{{ $rec->secondary_bank_name }} - {{ $rec->secondary_account_number }}</div>
                            <div style="font-size: 7pt; color: #64748b;">a.n. {{ $rec->secondary_account_holder }}</div>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td style="font-size: 7.5pt; color: #334155;">
                        {{ $rec->notes ?: '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 3. Kolom Tanda Tangan Alur Persetujuan (2 Tahap) -->
    <div class="section-header">3. Otorisasi & Persetujuan (2 Tahap)</div>
    @php
        $mgrAppr = $disposition->approvals->where('stage', 'manager')->first();
        $finAppr = $disposition->approvals->where('stage', 'finance')->first();
    @endphp

    <table class="signatures-table">
        <tr>
            <td>
                <div class="sig-role">1. Diajukan oleh (Admin/Finance):</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $disposition->applicant?->name ?? 'Admin / Finance' }}</div>
                <div class="sig-date">{{ $disposition->disposition_date ? $disposition->disposition_date->format('d/m/Y') : '' }}</div>
            </td>
            <td>
                <div class="sig-role">2. Persetujuan Manager:</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $mgrAppr?->actor?->name ?? '( Manager )' }}</div>
                <div class="sig-date">{{ $mgrAppr ? $mgrAppr->created_at->format('d/m/Y') : ($disposition->approval_status === 'waiting_manager' ? 'Menunggu Persetujuan' : '-') }}</div>
            </td>
            <td>
                <div class="sig-role">3. Keputusan Akhir Finance (Sesuai Arahan Owner):</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ ($finAppr && $disposition->approval_status === 'approved') ? $finAppr->actor?->name : '( Finance )' }}</div>
                <div class="sig-date">{{ ($finAppr && $disposition->approval_status === 'approved') ? $finAppr->created_at->format('d/m/Y') : ($disposition->approval_status === 'waiting_finance' ? 'Menunggu Keputusan' : '-') }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 15px; font-size: 7pt; color: #94a3b8; text-align: right;">
        Dicetak otomatis oleh Sistem JMM Disposisi pada {{ now()->translatedFormat('d F Y H:i:s') }} WITA
    </div>
</body>
</html>

