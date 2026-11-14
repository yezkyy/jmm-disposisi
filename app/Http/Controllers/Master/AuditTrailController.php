<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditTrailController extends Controller
{
    /**
     * Tampilkan riwayat log audit aktivitas sistem (Read-only).
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('auditable_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $logs = $query->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get();

        $breadcrumbs = [
            ['label' => 'Audit & Keamanan', 'url' => ''],
            ['label' => 'Jejak Audit Aktivitas', 'url' => route('master.audit.index')],
        ];

        return view('master.audit.index', compact('logs', 'users', 'breadcrumbs'));
    }
}

