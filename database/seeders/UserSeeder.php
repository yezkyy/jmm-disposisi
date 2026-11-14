<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hanya membuat akun demo/lokal jika diizinkan atau di lingkungan lokal
        if (!app()->environment('local', 'testing') && !env('SEED_DEMO_USERS', false)) {
            return;
        }

        $password = env('INITIAL_USER_PASSWORD', 'password');

        $roles = Role::pluck('id', 'name');
        $divisions = Division::pluck('id', 'code');

        $users = [
            [
                'id' => 1,
                'name' => 'Admin Keuangan',
                'email' => 'admin@jmmdisposisi.my.id',
                'old_email' => 'admin@jmm.test',
                'role_id' => $roles[Role::ROLE_ADMIN_FINANCE] ?? null,
                'division_id' => $divisions['FIN'] ?? null,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Manager Operasional',
                'email' => 'manager@jmmdisposisi.my.id',
                'old_email' => 'manager@jmm.test',
                'role_id' => $roles[Role::ROLE_MANAGER] ?? null,
                'division_id' => $divisions['OPS'] ?? null,
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'Pemohon Lapangan',
                'email' => 'pemohon@jmmdisposisi.my.id',
                'old_email' => 'pemohon@jmm.test',
                'role_id' => $roles[Role::ROLE_PEMOHON] ?? null,
                'division_id' => $divisions['PRJ'] ?? null,
                'is_active' => true,
            ],
            [
                'id' => 4,
                'name' => 'Bapak Direktur / Owner',
                'email' => 'owner@jmmdisposisi.my.id',
                'old_email' => 'owner@jmm.test',
                'role_id' => $roles[Role::ROLE_OWNER] ?? null,
                'division_id' => null,
                'is_active' => true,
            ],
        ];

        foreach ($users as $data) {
            $user = User::where('email', $data['email'])
                ->orWhere('email', $data['old_email'])
                ->orWhere('id', $data['id'])
                ->first();

            if ($user) {
                $user->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($password),
                    'role_id' => $data['role_id'],
                    'division_id' => $data['division_id'],
                    'is_active' => $data['is_active'],
                ]);
            } else {
                User::create([
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($password),
                    'role_id' => $data['role_id'],
                    'division_id' => $data['division_id'],
                    'is_active' => $data['is_active'],
                ]);
            }
        }
    }
}
