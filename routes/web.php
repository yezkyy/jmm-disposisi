<?php

use App\Http\Controllers\Approval\ApprovalController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Disposition\DispositionController;
use App\Http\Controllers\Disposition\DispositionDocumentController;
use App\Http\Controllers\Master\AuditTrailController;
use App\Http\Controllers\Master\BankAccountController;
use App\Http\Controllers\Master\BackupController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\CompanySettingController;
use App\Http\Controllers\Master\DivisionController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Report\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Application Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Dashboard Utama
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Modul Disposisi (Tahap 3 & Tahap 6 Dokumen)
    Route::prefix('dispositions')->name('dispositions.')->group(function () {
        Route::get('/', [DispositionController::class, 'index'])->name('index');
        Route::get('/create', [DispositionController::class, 'create'])->name('create');
        Route::post('/', [DispositionController::class, 'store'])->name('store');
        Route::get('/{disposition}', [DispositionController::class, 'show'])->name('show');
        Route::get('/{disposition}/edit', [DispositionController::class, 'edit'])->name('edit');
        Route::put('/{disposition}', [DispositionController::class, 'update'])->name('update');
        Route::post('/{disposition}/submit', [DispositionController::class, 'submit'])->name('submit');
        Route::delete('/{disposition}', [DispositionController::class, 'destroy'])->name('destroy');

        // Dokumen & Cetak (Tahap 6)
        Route::get('/{disposition}/pdf', [DispositionDocumentController::class, 'pdf'])->name('pdf');
        Route::get('/{disposition}/print', [DispositionDocumentController::class, 'print'])->name('print');
    });

    // Modul Persetujuan / Approval Workflow (Tahap 4)
    Route::middleware('role:manager,admin_finance,owner')->prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/{disposition}/review', [ApprovalController::class, 'review'])->name('review');
        Route::post('/{disposition}/decide', [ApprovalController::class, 'decide'])->name('decide');
    });

    // Modul Realisasi Pembayaran (Tahap 5)
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');

        Route::middleware('role:admin_finance,owner')->group(function () {
            Route::get('/create', [PaymentController::class, 'create'])->name('create');
            Route::post('/', [PaymentController::class, 'store'])->name('store');
            Route::post('/{payment}/reverse', [PaymentController::class, 'reverse'])->name('reverse');
        });

        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    });

    // Modul Laporan & Analitik (Tahap 6)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export-excel', [ReportController::class, 'exportExcel'])->name('export-excel');
    });

    // Master Rekening (Dapat diakses oleh seluruh pengguna terotentikasi, pembuatan in-place diizinkan untuk semua peran)
    Route::prefix('master/bank-accounts')->name('master.bank-accounts.')->group(function () {
        Route::get('/', [BankAccountController::class, 'index'])->name('index');
        Route::post('/', [BankAccountController::class, 'store'])->name('store');

        Route::middleware('role:admin_finance')->group(function () {
            Route::get('/create', [BankAccountController::class, 'create'])->name('create');
            Route::get('/{bankAccount}/edit', [BankAccountController::class, 'edit'])->name('edit');
            Route::put('/{bankAccount}', [BankAccountController::class, 'update'])->name('update');
            Route::patch('/{bankAccount}/toggle-status', [BankAccountController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{bankAccount}', [BankAccountController::class, 'destroy'])->name('destroy');
        });
    });

    // Master Khusus Administrasi (Hanya Admin / Finance)
    Route::middleware('role:admin_finance')->prefix('master')->name('master.')->group(function () {
        // Manajemen Pengguna & Hak Akses
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
            Route::put('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        });

        // Master Divisi
        Route::prefix('divisions')->name('divisions.')->group(function () {
            Route::get('/', [DivisionController::class, 'index'])->name('index');
            Route::get('/create', [DivisionController::class, 'create'])->name('create');
            Route::post('/', [DivisionController::class, 'store'])->name('store');
            Route::get('/{division}/edit', [DivisionController::class, 'edit'])->name('edit');
            Route::put('/{division}', [DivisionController::class, 'update'])->name('update');
            Route::patch('/{division}/toggle-status', [DivisionController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{division}', [DivisionController::class, 'destroy'])->name('destroy');
        });

        // Master Kategori Pengeluaran
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::get('/create', [CategoryController::class, 'create'])->name('create');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
            Route::patch('/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        });
    });

    // Pengaturan Perusahaan, Audit Trail & Backup (Admin Finance & Owner - Tahap 7)
    Route::middleware('role:admin_finance,owner')->prefix('master')->name('master.')->group(function () {
        // Pengaturan Identitas Perusahaan
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [CompanySettingController::class, 'index'])->name('index');
            Route::put('/', [CompanySettingController::class, 'update'])->name('update');
        });

        // Jejak Audit Aktivitas (Read-only)
        Route::prefix('audit')->name('audit.')->group(function () {
            Route::get('/', [AuditTrailController::class, 'index'])->name('index');
        });

        // Backup & Pemulihan Sistem
        Route::prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::post('/', [BackupController::class, 'store'])->name('store');
            Route::get('/{backupLog}/download', [BackupController::class, 'download'])->name('download');
            Route::post('/{backupLog}/verify', [BackupController::class, 'verify'])->name('verify');
        });
    });
});










