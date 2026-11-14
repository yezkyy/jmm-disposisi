<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $adminRole;
    protected Role $pemohonRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->adminRole = Role::where('name', Role::ROLE_ADMIN_FINANCE)->first();
        $this->pemohonRole = Role::where('name', Role::ROLE_PEMOHON)->first();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $this->adminRole->id,
            'is_active' => true,
        ]);
    }

    /**
     * 9. CRUD Pengguna: Buat pengguna baru.
     */
    public function test_admin_can_create_new_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('master.users.store'), [
            'name' => 'Karyawan Baru',
            'email' => 'karyawan@jmm.test',
            'password' => 'PasswordAman123!',
            'role_id' => $this->pemohonRole->id,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('master.users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Karyawan Baru',
            'email' => 'karyawan@jmm.test',
            'role_id' => $this->pemohonRole->id,
            'is_active' => true,
        ]);
    }

    /**
     * 13. Email duplikat ditolak saat create user.
     */
    public function test_duplicate_email_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('master.users.store'), [
            'name' => 'User Duplikat',
            'email' => 'admin@jmm.test', // Email sudah dipakai admin
            'password' => 'PasswordAman123!',
            'role_id' => $this->pemohonRole->id,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Update pengguna.
     */
    public function test_admin_can_update_user(): void
    {
        $targetUser = User::create([
            'name' => 'Target Nama',
            'email' => 'target@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $this->pemohonRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('master.users.update', $targetUser), [
            'name' => 'Target Terupdate',
            'email' => 'target.updated@jmm.test',
            'role_id' => $this->pemohonRole->id,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('master.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Target Terupdate',
            'email' => 'target.updated@jmm.test',
        ]);
    }

    /**
     * Toggle status aktif pengguna.
     */
    public function test_admin_can_toggle_user_status(): void
    {
        $targetUser = User::create([
            'name' => 'Target Status',
            'email' => 'target.status@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $this->pemohonRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('master.users.toggle-status', $targetUser));

        $response->assertSessionHas('success');
        $this->assertFalse((bool) $targetUser->fresh()->is_active);
    }

    /**
     * Reset password pengguna.
     */
    public function test_admin_can_reset_user_password(): void
    {
        $targetUser = User::create([
            'name' => 'Target Reset',
            'email' => 'target.reset@jmm.test',
            'password' => Hash::make('OldPassword123!'),
            'role_id' => $this->pemohonRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('master.users.reset-password', $targetUser), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('NewPassword123!', $targetUser->fresh()->password));
    }

    /**
     * Admin terakhir tidak boleh menonaktifkan dirinya sendiri.
     */
    public function test_last_admin_cannot_deactivate_themselves(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('master.users.toggle-status', $this->admin));

        $response->assertSessionHas('error');
        $this->assertTrue((bool) $this->admin->fresh()->is_active);
    }
}

