<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    /**
     * 6 & 7. Role middleware mencegah akses pengguna tanpa peran yang berwenang (HTTP 403).
     */
    public function test_non_admin_finance_receives_403_when_accessing_user_management(): void
    {
        $pemohonRole = Role::where('name', Role::ROLE_PEMOHON)->first();
        $pemohon = User::create([
            'name' => 'Pemohon Test',
            'email' => 'pemohon@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $pemohonRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($pemohon)->get(route('master.users.index'));

        $response->assertStatus(403);
    }

    /**
     * Admin/Finance dapat mengakses manajemen pengguna.
     */
    public function test_admin_finance_can_access_user_management(): void
    {
        $adminRole = Role::where('name', Role::ROLE_ADMIN_FINANCE)->first();
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('master.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Pengguna');
    }

    /**
     * 8. User tidak dapat mengubah role akunnya sendiri.
     */
    public function test_user_cannot_change_their_own_role(): void
    {
        $adminRole = Role::where('name', Role::ROLE_ADMIN_FINANCE)->first();
        $managerRole = Role::where('name', Role::ROLE_MANAGER)->first();

        $admin = User::create([
            'name' => 'Admin Keuangan',
            'email' => 'admin@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('master.users.update', $admin), [
            'name' => 'Admin Keuangan',
            'email' => 'admin@jmm.test',
            'role_id' => $managerRole->id, // Mencoba mengubah peran sendiri
            'is_active' => 1,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals($adminRole->id, $admin->fresh()->role_id);
    }
}

