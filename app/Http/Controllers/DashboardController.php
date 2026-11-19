<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Disposition;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard utama dengan data analitik live.
     */
    public function index(): View
    {
        $user = Auth::user();

        // 1. KPI Metrik Disposisi
        $totalDispositions = Disposition::count();
        $approvedDispositions = Disposition::whereIn('approval_status', [Disposition::STATUS_APPROVED, Disposition::STATUS_PARTIALLY_APPROVED])->count();
        $pendingDispositions = Disposition::whereIn('approval_status', [
            Disposition::STATUS_WAITING_MANAGER,
            Disposition::STATUS_WAITING_FINANCE,
            Disposition::STATUS_WAITING_OWNER,
        ])->count();

        // 2. Metrik Finansial
        $totalRequested = (float) Disposition::sum('requested_total');
        
        $totalApproved = (float) Disposition::whereIn('approval_status', [
            Disposition::STATUS_APPROVED,
            Disposition::STATUS_PARTIALLY_APPROVED
        ])->sum(DB::raw('COALESCE(final_approved_total, requested_total)'));

        $totalPaid = (float) Payment::where('status', Payment::STATUS_POSTED)->sum('amount');
        $totalRemaining = max(0, $totalApproved - $totalPaid);

        // 3. Tugas Antrean Tindakan Sesuai Role Pengguna (Pending Tasks)
        $pendingTasksQuery = Disposition::with(['applicant', 'division'])->latest();
        if ($user->isPemohon()) {
            // Pemohon: draft atau revisi yang perlu tindakan
            $pendingTasksQuery->where('applicant_id', $user->id)
                ->whereIn('approval_status', [Disposition::STATUS_DRAFT, Disposition::STATUS_REVISION_REQUESTED]);
        } elseif ($user->isManager()) {
            $pendingTasksQuery->where('approval_status', Disposition::STATUS_WAITING_MANAGER)
                ->where('applicant_id', '!=', $user->id);
        } elseif ($user->isAdminFinance()) {
            $pendingTasksQuery->where('approval_status', Disposition::STATUS_WAITING_FINANCE)
                ->where('applicant_id', '!=', $user->id);
        } elseif ($user->isOwner()) {
            $pendingTasksQuery->where('approval_status', Disposition::STATUS_WAITING_OWNER)
                ->where('applicant_id', '!=', $user->id);
        }
        $pendingTasks = $pendingTasksQuery->take(5)->get();

        // 4. Disposisi Terbaru
        $recentDispositions = Disposition::with(['applicant', 'division'])
            ->latest('disposition_date')
            ->take(5)
            ->get();

        // 5. Data Tren 6 Bulan Terakhir untuk ApexCharts
        $months = [];
        $trendRequested = [];
        $trendPaid = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;
            $monthLabel = $date->translatedFormat('M Y');
            $months[] = $monthLabel;

            $reqSum = Disposition::whereYear('disposition_date', $year)
                ->whereMonth('disposition_date', $month)
                ->sum('requested_total');
            $trendRequested[] = (float) $reqSum;

            $paidSum = Payment::where('status', Payment::STATUS_POSTED)
                ->whereYear('payment_date', $year)
                ->whereMonth('payment_date', $month)
                ->sum('amount');
            $trendPaid[] = (float) $paidSum;
        }

        // 6. Kategori Pengeluaran Terbesar (Donut Chart)
        $categoryData = DB::table('disposition_items')
            ->join('categories', 'disposition_items.category_id', '=', 'categories.id')
            ->select('categories.name as cat_name', DB::raw('SUM(disposition_items.amount) as cat_total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('cat_total')
            ->take(5)
            ->get();

        $categoryLabels = $categoryData->pluck('cat_name')->toArray();
        $categoryTotals = $categoryData->pluck('cat_total')->map(fn($v) => (float)$v)->toArray();

        // Fallback jika belum ada data kategori
        if (empty($categoryLabels)) {
            $categoryLabels = ['Operasional', 'Logistik', 'BBM', 'Maintenance'];
            $categoryTotals = [0, 0, 0, 0];
        }

        // 7. Summary Harian & Rekapitulasi Tanggal (Revisi Versi 3 Poin 5)
        $todayDate = now()->toDateString();
        $todayQuery = Disposition::whereDate('disposition_date', $todayDate);
        if ($user->isPemohon()) {
            $todayQuery->where('applicant_id', $user->id);
        }

        $todayCount = (clone $todayQuery)->count();
        $todayNominal = (float) (clone $todayQuery)->sum('requested_total');
        $todayApproved = (float) (clone $todayQuery)->whereIn('approval_status', [
            Disposition::STATUS_APPROVED,
            Disposition::STATUS_PARTIALLY_APPROVED
        ])->sum(DB::raw('COALESCE(final_approved_total, requested_total)'));

        // Rekapitulasi Harian Berdasarkan Tanggal (14 Hari Aktivitas Terakhir)
        $dailySummaryQuery = DB::table('dispositions')
            ->select(
                'disposition_date',
                DB::raw('COUNT(id) as total_count'),
                DB::raw('SUM(requested_total) as total_requested'),
                DB::raw('SUM(CASE WHEN approval_status IN ("approved", "partially_approved") THEN COALESCE(final_approved_total, requested_total) ELSE 0 END) as total_approved'),
                DB::raw('SUM(paid_total) as total_paid')
            )
            ->whereNotNull('disposition_date');

        if ($user->isPemohon()) {
            $dailySummaryQuery->where('applicant_id', $user->id);
        }

        $dailySummary = $dailySummaryQuery->groupBy('disposition_date')
            ->orderByDesc('disposition_date')
            ->take(14)
            ->get();

        $breadcrumbs = [
            ['label' => 'Dashboard', 'url' => '']
        ];

        return view('dashboard.index', compact(
            'totalDispositions',
            'approvedDispositions',
            'pendingDispositions',
            'totalRequested',
            'totalApproved',
            'totalPaid',
            'totalRemaining',
            'pendingTasks',
            'recentDispositions',
            'months',
            'trendRequested',
            'trendPaid',
            'categoryLabels',
            'categoryTotals',
            'todayCount',
            'todayNominal',
            'todayApproved',
            'dailySummary',
            'breadcrumbs'
        ));
    }
}
