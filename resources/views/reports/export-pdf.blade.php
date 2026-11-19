<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Realisasi Disposisi JMM</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 8mm 10mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 7.5pt;
            color: #0f172a;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .logo-img {
            max-height: 48px;
            max-width: 100px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-tagline {
            font-size: 7.5pt;
            font-style: italic;
            color: #475569;
            margin: 1px 0;
        }
        .company-contact {
            font-size: 7pt;
            color: #64748b;
            margin: 0;
        }
        .doc-title-box {
            text-align: center;
            margin-bottom: 8px;
        }
        .doc-title {
            font-size: 10.5pt;
            font-weight: bold;
            color: #026ec4;
            text-transform: uppercase;
            margin: 0;
        }
        .doc-period {
            font-size: 8pt;
            color: #475569;
            font-style: italic;
            margin-top: 2px;
        }
        .summary-boxes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .summary-box {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            background-color: #f8fafc;
            text-align: center;
        }
        .summary-label {
            font-size: 6.5pt;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .summary-val {
            font-size: 8.5pt;
            font-weight: bold;
            font-family: 'Courier', monospace;
            color: #0f172a;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }
        .data-table th {
            background-color: #026ec4;
            color: #ffffff;
            border: 1px solid #000000;
            padding: 4px 5px;
            text-transform: uppercase;
            font-weight: bold;
            text-align: left;
        }
        .data-table td {
            border: 1px solid #cbd5e1;
            padding: 3.5px 5px;
            vertical-align: top;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier', monospace; font-size: 7.5pt; }
        .font-bold { font-weight: bold; }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 6pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-approved { background-color: #dcfce7; color: #166534; }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-rejected { background-color: #fee2e2; color: #991b1b; }
        .badge-draft { background-color: #f1f5f9; color: #475569; }
    </style>
</head>
<body>
    <!-- Kop Surat -->
    <table class="header-table">
        <tr>
            @if ($logoBase64)
                <td style="width: 80px; vertical-align: middle;">
                    <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                </td>
            @endif
            <td style="vertical-align: middle;">
                <h1 class="company-name">{{ $companyName }}</h1>
                <p class="company-tagline">{{ $companyTagline }}</p>
                <p class="company-contact">{{ $companyAddress }} &bull; Telp: {{ $companyPhone }} &bull; Email: {{ $companyEmail }}</p>
            </td>
            <td style="text-align: right; vertical-align: top; font-size: 7pt; color: #64748b;">
                Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WITA<br>
                Sistem Disposisi Digital JMM
            </td>
        </tr>
    </table>

    <!-- Judul & Periode Laporan -->
    <div class="doc-title-box">
        <h2 class="doc-title">LAPORAN REKAPITULASI DISPOSISI PENGELUARAN DANA</h2>
        <div class="doc-period">
            Periode: 
            <strong>{{ !empty($filters['start_date']) ? \Carbon\Carbon::parse($filters['start_date'])->translatedFormat('d F Y') : 'Semua Tanggal' }}</strong>
            s/d 
            <strong>{{ !empty($filters['end_date']) ? \Carbon\Carbon::parse($filters['end_date'])->translatedFormat('d F Y') : 'Sekarang' }}</strong>
        </div>
    </div>

    <!-- Ringkasan Finansial Widget -->
    <table class="summary-boxes">
        <tr>
            <td class="summary-box" style="width: 20%;">
                <div class="summary-label">Total Disposisi</div>
                <div class="summary-val">{{ number_format($summary['count']) }} Berkas</div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="summary-label">Total Pengajuan</div>
                <div class="summary-val">Rp {{ number_format($summary['total_requested'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="summary-label">Total Disetujui</div>
                <div class="summary-val" style="color: #026ec4;">Rp {{ number_format($summary['total_approved'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="summary-label">Total Dibayar</div>
                <div class="summary-val" style="color: #16a34a;">Rp {{ number_format($summary['total_paid'], 0, ',', '.') }}</div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="summary-label">Sisa Tagihan</div>
                <div class="summary-val" style="color: #ea580c;">Rp {{ number_format($summary['total_remaining'], 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <!-- Tabel Data Disposisi -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;" class="text-center">No</th>
                <th style="width: 10%;">No. Disposisi</th>
                <th style="width: 8%;">Tanggal</th>
                <th style="width: 10%;">Pemohon / Divisi</th>
                <th style="width: 17%;">Perihal & Uraian Kebutuhan</th>
                <th style="width: 12%;">Penerima & Rekening</th>
                <th style="width: 9%;" class="text-right">Diajukan (Rp)</th>
                <th style="width: 9%;" class="text-right">Disetujui (Rp)</th>
                <th style="width: 8%;" class="text-right">Dibayar (Rp)</th>
                <th style="width: 8%;" class="text-right">Sisa (Rp)</th>
                <th style="width: 6%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dispositions as $idx => $d)
                @php
                    $approved = (float) ($d->final_approved_total ?? (in_array($d->approval_status, ['approved', 'partially_approved']) ? $d->requested_total : 0));
                    $paid = (float) $d->paid_total;
                    $remaining = max(0, $approved - $paid);
                    $firstRec = $d->recipients->first();
                @endphp
                <tr style="{{ $idx % 2 == 1 ? 'background-color: #f8fafc;' : '' }}">
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-mono">
                        <strong>{{ $d->disposition_number ?: 'Draft #' . $d->id }}</strong>
                        @if ($d->invoice_number)
                            <div style="font-size: 6pt; color: #026ec4;">Inv: {{ $d->invoice_number }}</div>
                        @endif
                    </td>
                    <td>{{ $d->disposition_date ? $d->disposition_date->format('d/m/Y') : '-' }}</td>
                    <td>
                        <strong>{{ $d->applicant?->name ?? '-' }}</strong><br>
                        <span style="color: #64748b;">{{ $d->division?->name ?? 'Operasional' }}</span>
                    </td>
                    <td>
                        <strong>{{ $d->subject }}</strong>
                        @if ($d->items->count() > 0)
                            <div style="font-size: 6.5pt; color: #475569; margin-top: 2px;">
                                &bull; {{ Str::limit($d->items->pluck('description')->join(', '), 90) }}
                            </div>
                        @endif
                    </td>
                    <td>
                        @if ($firstRec)
                            <strong>{{ $firstRec->recipient_name }}</strong><br>
                            <span style="font-size: 6pt; color: #64748b;">
                                {{ $firstRec->primary_bank_name ?: '-' }} &bull; {{ $firstRec->primary_account_number ?: '-' }}
                            </span>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td class="text-right font-mono">{{ number_format((float) $d->requested_total, 0, ',', '.') }}</td>
                    <td class="text-right font-mono font-bold" style="color: #026ec4;">{{ number_format($approved, 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="color: #16a34a;">{{ number_format($paid, 0, ',', '.') }}</td>
                    <td class="text-right font-mono" style="color: #ea580c;">{{ number_format($remaining, 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if ($d->approval_status === 'approved')
                            <span class="badge badge-approved">Disetujui</span>
                        @elseif ($d->approval_status === 'rejected')
                            <span class="badge badge-rejected">Ditolak</span>
                        @elseif ($d->approval_status === 'draft')
                            <span class="badge badge-draft">Draft</span>
                        @else
                            <span class="badge badge-pending">Proses</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 15px; color: #94a3b8;">
                        Tidak ada data disposisi pada periode yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #0f172a;">
                <td colspan="6" class="text-right" style="padding: 5px;">TOTAL AKUMULASI PERIODE INI:</td>
                <td class="text-right font-mono">{{ number_format($summary['total_requested'], 0, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #026ec4;">{{ number_format($summary['total_approved'], 0, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #16a34a;">{{ number_format($summary['total_paid'], 0, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #ea580c;">{{ number_format($summary['total_remaining'], 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
