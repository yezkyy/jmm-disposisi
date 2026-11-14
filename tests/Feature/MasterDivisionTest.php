<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDivisionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $adminRole = Role::where('name', Role::ROLE_ADMIN_FINANCE)->first();
        $this->admin = User::create([
            'name' => 'Admin Keuangan',
            'email' => 'admin@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
    }

    /**
     * 10. CRUD Divisi: Buat, edit, toggle status, dan hapus.
     */
    public function test_admin_can_create_division(): void
    {
        $response = $this->actingAs($this->admin)->post(route('master.divisions.store'), [
            'name' => 'Divisi Logistik',
            'code' => 'LOG',
            'description' => 'Operasional Armada dan Logistik',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('master.divisions.index'));
        $this->assertDatabaseHas('divisions', [
            'name' => 'Divisi Logistik',
            'code' => 'LOG',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_division(): void
    {
        $division = Division::create([
            'name' => 'Divisi Lama',
            'code' => 'OLD',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('master.divisions.update', $division), [
            'name' => 'Divisi Baru',
            'code' => 'NEW',
            'description' => 'Deskripsi Baru',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('master.divisions.index'));
        $this->assertDatabaseHas('divisions', [
            'id' => $division->id,
            'name' => 'Divisi Baru',
            'code' => 'NEW',
        ]);
    }

    public function test_admin_can_toggle_division_status(): void
    {
        $division = Division::create([
            'name' => 'Divisi Status',
            'code' => 'STA',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('master.divisions.toggle-status', $division));

        $response->assertSessionHas('success');
        $this->assertFalse((bool) $division->fresh()->is_active);
    }

    public function test_division_with_users_cannot_be_deleted(): void
    {
        $division = Division::create([
            'name' => 'Divisi Memiliki Pengguna',
            'code' => 'HAS_USER',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Anggota Divisi',
            'email' => 'anggota@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => Role::where('name', Role::ROLE_PEMOHON)->first()->id,
            'division_id' => $division->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('master.divisions.destroy', $division));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('divisions', ['id' => $division->id]);
    }
}

