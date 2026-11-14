<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Services\Backup\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    /**
     * Tampilkan riwayat backup dan panel pemulihan sistem.
     */
    public function index(): View
    {
        $backups = BackupLog::with('creator')->latest('created_at')->paginate(10);

        $breadcrumbs = [
            ['label' => 'Audit & Keamanan', 'url' => ''],
            ['label' => 'Backup & Pemulihan Sistem', 'url' => route('master.backups.index')],
        ];

        return view('master.backups.index', compact('backups', 'breadcrumbs'));
    }

    /**
     * Jalankan proses backup sesuai tipe yang dipilih.
     */
    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type', 'sql');
        $user = Auth::user();

        if ($type === 'attachments') {
            $backup = $this->backupService->createAttachmentsBackup($user);
            $label = 'Arsip berkas lampiran';
        } elseif ($type === 'zip') {
            $backup = $this->backupService->createFullBackup($user);
            $label = 'Paket pemulihan bencana lengkap (Full Disaster Recovery)';
        } else {
            $backup = $this->backupService->createDatabaseBackup($user);
            $label = 'Dump SQL database';
        }

        if ($backup->status === 'completed') {
            return redirect()->route('master.backups.index')
                ->with('success', "{$label} berhasil dibuat ({$backup->formatted_size}). File: {$backup->file_name}");
        }

        return redirect()->route('master.backups.index')
            ->with('error', "Pembuatan backup gagal: {$backup->notes}");
    }

    /**
     * Unduh berkas backup secara aman.
     */
    public function download(BackupLog $backupLog): BinaryFileResponse|RedirectResponse
    {
        $fullPath = storage_path("app/{$backupLog->file_path}");

        if (!File::exists($fullPath)) {
            return back()->with('error', 'Berkas backup fisik tidak ditemukan di server.');
        }

        return response()->download($fullPath, $backupLog->file_name);
    }

    /**
     * Verifikasi integritas file backup (simulasi pemulihan non-destruktif).
     */
    public function verify(BackupLog $backupLog): JsonResponse|RedirectResponse
    {
        $result = $this->backupService->verifyBackup($backupLog);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($result);
        }

        if ($result['valid']) {
            return redirect()->route('master.backups.index')
                ->with('success', "Verifikasi Sukses: {$result['message']}");
        }

        return redirect()->route('master.backups.index')
            ->with('error', "Verifikasi Gagal: {$result['message']}");
    }
}
