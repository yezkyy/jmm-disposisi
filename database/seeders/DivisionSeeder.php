<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            ['name' => 'Operasional', 'code' => 'OPS', 'description' => 'Divisi Kegiatan Operasional Lapangan & Kantor'],
            ['name' => 'Finance & Accounting', 'code' => 'FIN', 'description' => 'Divisi Keuangan, Akuntansi, dan Pembayaran'],
            ['name' => 'Logistik & Armada', 'code' => 'LOG', 'description' => 'Divisi Pengelolaan Logistik, Kendaraan, dan Supir'],
            ['name' => 'HR & General Affairs', 'code' => 'HRGA', 'description' => 'Divisi Personalia dan Urusan Umum Perusahaan'],
            ['name' => 'Proyek & Lapangan', 'code' => 'PRJ', 'description' => 'Divisi Manajemen Pekerjaan & Proyek Lapangan'],
        ];

        foreach ($divisions as $div) {
            Division::updateOrCreate(
                ['code' => $div['code']],
                [
                    'name' => $div['name'],
                    'description' => $div['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}

