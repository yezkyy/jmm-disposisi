<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Disposition;
use App\Models\DispositionApproval;
use App\Models\Division;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleBusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        $pemohon = User::where('email', 'pemohon@jmmdisposisi.my.id')->orWhere('email', 'pemohon@jmm.test')->first();
        $manager = User::where('email', 'manager@jmmdisposisi.my.id')->orWhere('email', 'manager@jmm.test')->first();
        $admin = User::where('email', 'admin@jmmdisposisi.my.id')->orWhere('email', 'admin@jmm.test')->first();
        $owner = User::where('email', 'owner@jmmdisposisi.my.id')->orWhere('email', 'owner@jmm.test')->first();

        // Divisi
        $divOps = Division::firstOrCreate(['code' => 'OPS'], [
            'name' => 'Operasional Logistik & Transportasi',
            'description' => 'Divisi armada truk dan pengiriman kargo logistik',
            'is_active' => true,
        ]);
        $divWorkshop = Division::firstOrCreate(['code' => 'WRK'], [
            'name' => 'Alat Berat & Workshop',
            'description' => 'Divisi pemeliharaan armada dan alat berat',
            'is_active' => true,
        ]);

        if ($pemohon && !$pemohon->division_id) {
            $pemohon->division_id = $divOps->id;
            $pemohon->save();
        }

        // Kategori
        $catBbm = Category::firstOrCreate(['code' => 'BBM'], [
            'name' => 'Bahan Bakar Minyak & Solar Industri',
            'description' => 'Solar armada dan alat berat',
            'is_active' => true,
        ]);
        $catSparepart = Category::firstOrCreate(['code' => 'SPR'], [
            'name' => 'Sparepart & Ban Armada',
            'description' => 'Ban tronton dan suku cadang',
            'is_active' => true,
        ]);
        $catDriver = Category::firstOrCreate(['code' => 'DRV'], [
            'name' => 'Uang Jalan & Honor Driver',
            'description' => 'Uang jalan sopir ekspedisi logistik',
            'is_active' => true,
        ]);

        // Rekening Perusahaan & Vendor
        $bankMandiri = Bank::firstOrCreate(['name' => 'Bank Mandiri'], ['code' => 'BMRI', 'is_active' => true]);
        $bankBca = Bank::firstOrCreate(['name' => 'Bank Central Asia'], ['code' => 'BBCA', 'is_active' => true]);

        $companyAccount = BankAccount::firstOrCreate(['account_number' => '1480098765432'], [
            'owner_name' => 'PT Jaya Mukti Mandiri',
            'bank_id' => $bankMandiri->id,
            'bank_name' => 'Bank Mandiri',
            'owner_type' => 'other',
            'is_active' => true,
        ]);

        $vendorAccount = BankAccount::firstOrCreate(['account_number' => '0700012345678'], [
            'owner_name' => 'PT Pertamina Patra Niaga Balikpapan',
            'bank_id' => $bankMandiri->id,
            'bank_name' => 'Bank Mandiri',
            'owner_type' => 'vendor',
            'is_active' => true,
        ]);

        $driverAccount = BankAccount::firstOrCreate(['account_number' => '01488776655'], [
            'owner_name' => 'Budi Santoso',
            'bank_id' => $bankBca->id,
            'bank_name' => 'BCA',
            'owner_type' => 'driver',
            'is_active' => true,
        ]);

        // Disposisi 1: Lunas & Terbit Voucher (Approved by Manager -> Finance -> Owner)
        $disp1 = Disposition::firstOrCreate(['disposition_number' => 'JMM/2026/0001'], [
            'disposition_date' => now()->subDays(5)->toDateString(),
            'subject' => 'Pengadaan Solar Industri 5.000 Liter Armada Balikpapan - Berau',
            'applicant_id' => $pemohon->id,
            'division_id' => $divOps->id,
            'description' => 'Pengadaan BBM solar industri untuk 5 unit truk tronton ekspedisi rute Kalimantan Timur.',
            'approval_status' => Disposition::STATUS_APPROVED,
            'payment_status' => Disposition::PAYMENT_PAID,
            'requested_total' => 67500000,
            'manager_approved_total' => 67500000,
            'finance_verified_total' => 67500000,
            'owner_approved_total' => 67500000,
            'final_approved_total' => 67500000,
            'paid_total' => 67500000,
            'submitted_at' => now()->subDays(5),
        ]);

        if ($disp1->items()->count() === 0) {
            $disp1->items()->create([
                'category_id' => $catBbm->id,
                'description' => 'Solar Industri B35 Non-Subsidi 5000L @ Rp 13.500',
                'amount' => 67500000,
            ]);
        }

        if ($disp1->recipients()->count() === 0) {
            $disp1->recipients()->create([
                'recipient_name' => 'PT Pertamina Patra Niaga Balikpapan',
                'recipient_type' => 'vendor',
                'primary_bank_account_id' => $vendorAccount->id,
                'primary_account_name' => 'PT Pertamina Patra Niaga Balikpapan',
                'primary_bank_name' => 'Bank Mandiri',
                'primary_account_number' => '0700012345678',
                'allocated_amount' => 67500000,
            ]);
        }

        if ($disp1->approvals()->count() === 0) {
            DispositionApproval::create([
                'disposition_id' => $disp1->id,
                'stage' => 'manager',
                'actor_id' => $manager->id,
                'decision' => 'approved',
                'previous_amount' => 67500000,
                'approved_amount' => 67500000,
                'notes' => 'Disetujui sesuai kuota rute logistik bulanan.',
                'created_at' => now()->subDays(4),
            ]);
            DispositionApproval::create([
                'disposition_id' => $disp1->id,
                'stage' => 'finance',
                'actor_id' => $admin->id,
                'decision' => 'approved',
                'previous_amount' => 67500000,
                'approved_amount' => 67500000,
                'notes' => 'Faktur dan kesesuaian harga vendor telah divalidasi.',
                'created_at' => now()->subDays(3),
            ]);
            DispositionApproval::create([
                'disposition_id' => $disp1->id,
                'stage' => 'owner',
                'actor_id' => $owner->id,
                'decision' => 'approved',
                'previous_amount' => 67500000,
                'approved_amount' => 67500000,
                'notes' => 'Persetujuan pengeluaran dana disetujui direktur.',
                'created_at' => now()->subDays(2),
            ]);
        }

        // Voucher Pembayaran untuk Disposisi 1
        if (Payment::where('disposition_id', $disp1->id)->count() === 0) {
            Payment::create([
                'disposition_id' => $disp1->id,
                'payment_number' => 'PAY/2026/11/0001',
                'payment_date' => now()->subDays(2)->toDateString(),
                'amount' => 67500000,
                'method' => 'transfer',
                'source_account_id' => $companyAccount->id,
                'recipient_id' => $disp1->recipients()->first()?->id,
                'reference_number' => 'TRF-MDR-20261111-9988',
                'notes' => 'Pelunasan transfer pembayaran solar via Mandiri Corporate',
                'recorded_by' => $admin->id,
                'status' => Payment::STATUS_POSTED,
            ]);
        }

        // Disposisi 2: Menunggu Persetujuan Manager
        $disp2 = Disposition::firstOrCreate(['disposition_number' => 'JMM/2026/0002'], [
            'disposition_date' => now()->subDays(1)->toDateString(),
            'subject' => 'Penggantian 4 Unit Ban Luar Tronton & Oli Gardan Komatsu PC200',
            'applicant_id' => $pemohon->id,
            'division_id' => $divWorkshop->id,
            'description' => 'Ban aus akibat operasional jalur tambang dan pergantian rutin pelumas gardan excavator.',
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
            'payment_status' => Disposition::PAYMENT_UNPAID,
            'requested_total' => 18400000,
            'submitted_at' => now()->subDays(1),
        ]);

        if ($disp2->items()->count() === 0) {
            $disp2->items()->create([
                'category_id' => $catSparepart->id,
                'description' => 'Ban Luar Bridgestone 11.00-20 (4 Pcs @ Rp 3.850.000)',
                'amount' => 15400000,
            ]);
            $disp2->items()->create([
                'category_id' => $catBbm->id,
                'description' => 'Oli Gardan & Transmisi Shell Spirax S2 A 140 (1 Pail)',
                'amount' => 3000000,
            ]);
        }

        if ($disp2->recipients()->count() === 0) {
            $disp2->recipients()->create([
                'recipient_name' => 'Toko Ban Sentosa Balikpapan',
                'recipient_type' => 'vendor',
                'primary_bank_name' => 'BCA',
                'primary_account_number' => '0142998811',
                'primary_account_name' => 'Toko Ban Sentosa',
                'allocated_amount' => 18400000,
            ]);
        }

        // Disposisi 3: Uang Jalan Sopir (Approved, Menunggu Pembayaran)
        $disp3 = Disposition::firstOrCreate(['disposition_number' => 'JMM/2026/0003'], [
            'disposition_date' => now()->toDateString(),
            'subject' => 'Uang Jalan & Operasional 3 Armada Angkutan Material Balikpapan - Samarinda',
            'applicant_id' => $pemohon->id,
            'division_id' => $divOps->id,
            'description' => 'Uang jalan sopir, uang tol, dan uang makan untuk ekspedisi 3 unit tronton.',
            'approval_status' => Disposition::STATUS_APPROVED,
            'payment_status' => Disposition::PAYMENT_UNPAID,
            'requested_total' => 4500000,
            'final_approved_total' => 4500000,
            'paid_total' => 0,
            'submitted_at' => now(),
        ]);

        if ($disp3->items()->count() === 0) {
            $disp3->items()->create([
                'category_id' => $catDriver->id,
                'description' => 'Uang Jalan Sopir Truk (3 Orang @ Rp 1.500.000)',
                'amount' => 4500000,
            ]);
        }

        if ($disp3->recipients()->count() === 0) {
            $disp3->recipients()->create([
                'recipient_name' => 'Budi Santoso (Driver Koordinator)',
                'recipient_type' => 'driver',
                'primary_bank_account_id' => $driverAccount->id,
                'primary_bank_name' => 'BCA',
                'primary_account_number' => '01488776655',
                'primary_account_name' => 'Budi Santoso',
                'allocated_amount' => 4500000,
            ]);
        }

        // Sample Backup Log
        if (BackupLog::count() === 0) {
            BackupLog::create([
                'backup_type' => 'sql',
                'file_name' => 'backup_jmm_db_initial.sql',
                'file_path' => 'backups/backup_jmm_db_initial.sql',
                'file_size' => 148500,
                'status' => 'completed',
                'created_by' => $admin->id,
                'started_at' => now()->subDays(1),
                'completed_at' => now()->subDays(1),
                'notes' => 'Backup inisialisasi sistem berhasil.',
                'created_at' => now()->subDays(1),
            ]);
        }

        // Sinkronkan urutan nomor disposisi untuk tahun 2026 agar nomor berikutnya tidak duplikat
        $maxSeq = \App\Models\DispositionSequence::getMaxExistingNumberForYear(2026);
        \App\Models\DispositionSequence::updateOrCreate(
            ['year' => 2026],
            ['last_number' => $maxSeq]
        );
    }
}
