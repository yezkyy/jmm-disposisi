<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'BBM & Bahan Bakar', 'code' => 'BBM', 'description' => 'Bahan bakar kendaraan operasional dan alat berat'],
            ['name' => 'Pemeliharaan & Sparepart Armada', 'code' => 'ARM', 'description' => 'Servis berkala, suku cadang, dan perbaikan armada'],
            ['name' => 'Operasional Kantor & Lapangan', 'code' => 'OPR', 'description' => 'Kebutuhan perlengkapan, listrik, ATK, dan konsumsi kerja'],
            ['name' => 'Pengadaan Material & Logistik', 'code' => 'MAT', 'description' => 'Pembelian bahan dan peralatan proyek logistik'],
            ['name' => 'Uang Saku & Perjalanan Dinas', 'code' => 'TRV', 'description' => 'Akomodasi, uang jalan supir, dan tiket tugas lapangan'],
            ['name' => 'Pengeluaran Lain-lain', 'code' => 'ETC', 'description' => 'Pengeluaran darurat atau kebutuhan di luar kategori umum'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}

