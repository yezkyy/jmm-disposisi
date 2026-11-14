<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::ROLE_PEMOHON,
                'display_name' => 'Pemohon',
                'description' => 'Pengguna yang membuat, menyimpan draft, dan mengirimkan pengajuan disposisi pengeluaran.',
            ],
            [
                'name' => Role::ROLE_MANAGER,
                'display_name' => 'Manager',
                'description' => 'Pemeriksa tahap pertama: persetujuan penuh, persetujuan sebagian, permintaan revisi, atau penolakan.',
            ],
            [
                'name' => Role::ROLE_ADMIN_FINANCE,
                'display_name' => 'Admin / Finance',
                'description' => 'Verifikator administrasi & keuangan, pengelola master data sistem, dan pencatat pembayaran disposisi.',
            ],
            [
                'name' => Role::ROLE_OWNER,
                'display_name' => 'Owner',
                'description' => 'Pemberi keputusan final pengeluaran: persetujuan penuh/sebagian, penolakan, serta pencatatan pembayaran.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                [
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                ]
            );
        }
    }
}

