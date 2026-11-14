<?php

namespace App\Services\Reporting;

use App\Models\Category;
use App\Models\Disposition;
use App\Models\Division;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Dapatkan query builder dasar disposisi dengan filter diterapkan.
     */
    public function getFilteredQuery(array $filters): Builder
    {
        $query = Disposition::with(['applicant', 'division', 'items.category', 'recipients'])->latest('disposition_date');

        if (!empty($filters['start_date'])) {
            $query->whereDate('disposition_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('disposition_date', '<=', $filters['end_date']);
        }
        if (!empty($filters['division_id'])) {
            $query->where('division_id', $filters['division_id']);
        }
        if (!empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (!empty($filters['category_id'])) {
            $query->whereHas('items', function ($iq) use ($filters) {
                $iq->where('category_id', $filters['category_id']);
            });
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('disposition_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('applicant', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('recipients', fn($rq) => $rq->where('recipient_name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * Hitung agregasi ringkasan finansial berdasarkan filter.
     */
    public function getSummary(array $filters): array
    {
        $query = $this->getFilteredQuery($filters);
        $dispositions = $query->get();

        $totalRequested = 0.0;
        $totalApproved = 0.0;
        $totalPaid = 0.0;

        foreach ($dispositions as $d) {
            $totalRequested += (float) $d->requested_total;
            $approved = (float) ($d->final_approved_total ?? (in_array($d->approval_status, ['approved', 'partially_approved']) ? $d->requested_total : 0));
            $totalApproved += $approved;
            $totalPaid += (float) $d->paid_total;
        }

        $totalRemaining = max(0, $totalApproved - $totalPaid);
        $payRate = $totalApproved > 0 ? round(($totalPaid / $totalApproved) * 100, 1) : 0;

        return [
            'count' => $dispositions->count(),
            'total_requested' => $totalRequested,
            'total_approved' => $totalApproved,
            'total_paid' => $totalPaid,
            'total_remaining' => $totalRemaining,
            'payment_rate' => $payRate,
        ];
    }

    /**
     * Breakdown pengeluaran per kategori.
     */
    public function getCategoryBreakdown(array $filters): array
    {
        $dispIds = $this->getFilteredQuery($filters)->pluck('id');

        return DB::table('disposition_items')
            ->join('categories', 'disposition_items.category_id', '=', 'categories.id')
            ->whereIn('disposition_items.disposition_id', $dispIds)
            ->select('categories.name as category_name', DB::raw('SUM(disposition_items.amount) as total_amount'), DB::raw('COUNT(disposition_items.id) as item_count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get()
            ->toArray();
    }

    /**
     * Export data laporan disposisi ke format Excel XLSX resmi.
     */
    public function exportExcel(array $filters): StreamedResponse
    {
        $dispositions = $this->getFilteredQuery($filters)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Disposisi');

        // Header Dokumen
        $sheet->setCellValue('A1', 'PT JAYA MUKTI MANDIRI');
        $sheet->setCellValue('A2', 'LAPORAN REALISASI DISPOSISI PENGELUARAN DANA');
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $periodText = 'Periode: ' . (!empty($filters['start_date']) ? $filters['start_date'] : 'Awal') . ' s/d ' . (!empty($filters['end_date']) ? $filters['end_date'] : 'Sekarang');
        $sheet->setCellValue('A3', $periodText);
        $sheet->mergeCells('A3:L3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(9);

        // Header Tabel Kolom
        $headers = [
            'A5' => 'NO',
            'B5' => 'NO. DISPOSISI',
            'C5' => 'TANGGAL',
            'D5' => 'PEMOHON',
            'E5' => 'DIVISI',
            'F5' => 'PERIHAL PENGELUARAN',
            'G5' => 'PENERIMA UTAMA',
            'H5' => 'PENGAJUAN (RP)',
            'I5' => 'DISETUJUI (RP)',
            'J5' => 'DIBAYAR (RP)',
            'K5' => 'SISA BAYAR (RP)',
            'L5' => 'STATUS APPROVAL',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue($col, $title);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '026EC4']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        $sheet->getStyle('A5:L5')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(26);

        // Baris Data
        $row = 6;
        foreach ($dispositions as $idx => $d) {
            $recName = $d->recipients->first()?->recipient_name ?? '-';
            $approved = (float) ($d->final_approved_total ?? (in_array($d->approval_status, ['approved', 'partially_approved']) ? $d->requested_total : 0));
            $paid = (float) $d->paid_total;
            $remaining = max(0, $approved - $paid);

            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $d->disposition_number ?: 'Draft #' . $d->id);
            $sheet->setCellValue("C{$row}", $d->disposition_date ? $d->disposition_date->format('d/m/Y') : '-');
            $sheet->setCellValue("D{$row}", $d->applicant?->name);
            $sheet->setCellValue("E{$row}", $d->division?->name ?? 'Operasional');
            $sheet->setCellValue("F{$row}", $d->subject);
            $sheet->setCellValue("G{$row}", $recName);
            $sheet->setCellValue("H{$row}", (float) $d->requested_total);
            $sheet->setCellValue("I{$row}", $approved);
            $sheet->setCellValue("J{$row}", $paid);
            $sheet->setCellValue("K{$row}", $remaining);
            $sheet->setCellValue("L{$row}", strtoupper(str_replace('_', ' ', $d->approval_status)));

            // Format cell values
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Format accounting currency
            $sheet->getStyle("H{$row}:K{$row}")->getNumberFormat()->setFormatCode('"Rp "#,##0.00');

            // Border
            $sheet->getStyle("A{$row}:L{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

            $row++;
        }

        // Summary Total Row
        $summaryRow = $row;
        $sheet->setCellValue("A{$summaryRow}", 'TOTAL AKUMULASI:');
        $sheet->mergeCells("A{$summaryRow}:G{$summaryRow}");
        $sheet->setCellValue("H{$summaryRow}", "=SUM(H6:H" . ($summaryRow - 1) . ")");
        $sheet->setCellValue("I{$summaryRow}", "=SUM(I6:I" . ($summaryRow - 1) . ")");
        $sheet->setCellValue("J{$summaryRow}", "=SUM(J6:J" . ($summaryRow - 1) . ")");
        $sheet->setCellValue("K{$summaryRow}", "=SUM(K6:K" . ($summaryRow - 1) . ")");

        $sheet->getStyle("A{$summaryRow}:L{$summaryRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$summaryRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H{$summaryRow}:K{$summaryRow}")->getNumberFormat()->setFormatCode('"Rp "#,##0.00');
        $sheet->getStyle("A{$summaryRow}:L{$summaryRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet->getStyle("A{$summaryRow}:L{$summaryRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto width
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Laporan_Disposisi_JMM_' . date('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

