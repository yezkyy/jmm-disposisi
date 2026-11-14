<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            ['name' => 'Bank Central Asia (BCA)', 'code' => '014'],
            ['name' => 'Bank Mandiri', 'code' => '008'],
            ['name' => 'Bank Rakyat Indonesia (BRI)', 'code' => '002'],
            ['name' => 'Bank Negara Indonesia (BNI)', 'code' => '009'],
            ['name' => 'Bank Syariah Indonesia (BSI)', 'code' => '451'],
            ['name' => 'CIMB Niaga', 'code' => '022'],
            ['name' => 'Bank Permata', 'code' => '013'],
            ['name' => 'Bank Danamon', 'code' => '011'],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(
                ['name' => $bank['name']],
                [
                    'code' => $bank['code'],
                    'is_active' => true,
                ]
            );
        }
    }
}

