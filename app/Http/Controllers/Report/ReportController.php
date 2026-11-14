<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Division;
use App\Services\Reporting\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Tampilkan halaman laporan dan analitik keuangan disposisi.
     */
    public function index(Request $request): View
    {
        $filters = $request->only([
            'start_date',
            'end_date',
            'division_id',
            'category_id',
            'approval_status',
            'payment_status',
            'search',
        ]);

        $summary = $this->reportService->getSummary($filters);
        $categoryBreakdown = $this->reportService->getCategoryBreakdown($filters);
        $dispositions = $this->reportService->getFilteredQuery($filters)->paginate(15)->withQueryString();

        $divisions = Division::active()->get();
        $categories = Category::active()->get();

        $breadcrumbs = [
            ['label' => 'Laporan & Analitik', 'url' => ''],
            ['label' => 'Laporan Realisasi Disposisi', 'url' => route('reports.index')],
        ];

        return view('reports.index', compact(
            'summary',
            'categoryBreakdown',
            'dispositions',
            'divisions',
            'categories',
            'filters',
            'breadcrumbs'
        ));
    }

    /**
     * Unduh berkas laporan dalam format Excel XLSX resmi.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'start_date',
            'end_date',
            'division_id',
            'category_id',
            'approval_status',
            'payment_status',
            'search',
        ]);

        return $this->reportService->exportExcel($filters);
    }
}

