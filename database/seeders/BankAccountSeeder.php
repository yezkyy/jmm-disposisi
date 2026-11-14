<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!app()->environment('local', 'testing')) {
            return;
        }

        $bca = Bank::where('code', '014')->first();
        $mandiri = Bank::where('code', '008')->first();
        $bri = Bank::where('code', '002')->first();
        $admin = User::first();

        $accounts = [
            [
                'owner_name' => 'PT Jaya Mukti Mandiri',
                'bank_id' => $bca?->id,
                'bank_name' => $bca?->name ?? 'Bank Central Asia (BCA)',
                'account_number' => '0881234567', // leading zero preserved!
                'owner_type' => BankAccount::TYPE_OTHER,
                'owner_reference' => 'KAS-PERUSAHAAN-01',
                'notes' => 'Rekening operasional utama perusahaan',
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'owner_name' => 'Bambang Supriyanto',
                'bank_id' => $mandiri?->id,
                'bank_name' => $mandiri?->name ?? 'Bank Mandiri',
                'account_number' => '0060012398745', // leading zero preserved!
                'owner_type' => BankAccount::TYPE_DRIVER,
                'owner_reference' => 'SOPIR-ARM-04',
                'notes' => 'Supir armada trailer logistik rute Balikpapan - Samarinda',
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'owner_name' => 'CV Sumber Berkah Mandiri',
                'bank_id' => $bri?->id,
                'bank_name' => $bri?->name ?? 'Bank Rakyat Indonesia (BRI)',
                'account_number' => '020601004826501', // leading zero preserved!
                'owner_type' => BankAccount::TYPE_VENDOR,
                'owner_reference' => 'VEND-SPAREPART-02',
                'notes' => 'Vendor bengkel dan suku cadang armada',
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'owner_name' => 'Ahmad Fauzi (Karyawan Ex)',
                'bank_id' => $bca?->id,
                'bank_name' => $bca?->name ?? 'Bank Central Asia (BCA)',
                'account_number' => '0912384721',
                'owner_type' => BankAccount::TYPE_EMPLOYEE,
                'owner_reference' => 'EMP-2022-09',
                'notes' => 'Rekening nonaktif mantan staf proyek',
                'is_active' => false,
                'created_by' => $admin?->id,
            ],
        ];

        foreach ($accounts as $acc) {
            BankAccount::updateOrCreate(
                ['account_number' => $acc['account_number']],
                $acc
            );
        }
    }
}

