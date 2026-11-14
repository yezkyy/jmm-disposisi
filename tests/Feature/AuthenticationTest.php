<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    /**
     * 1. Login berhasil.
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        $role = Role::where('name', Role::ROLE_PEMOHON)->first();
        $user = User::create([
            'name' => 'Budi Pemohon',
            'email' => 'budi@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'budi@jmm.test',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 2. Login gagal dengan pesan generik.
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        $role = Role::where('name', Role::ROLE_PEMOHON)->first();
        User::create([
            'name' => 'Budi Pemohon',
            'email' => 'budi@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'budi@jmm.test',
            'password' => 'WrongPassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * 3. Logout menggunakan POST.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $role = Role::where('name', Role::ROLE_PEMOHON)->first();
        $user = User::create([
            'name' => 'Budi Pemohon',
            'email' => 'budi@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * 4. User nonaktif tidak bisa login.
     */
    public function test_inactive_user_cannot_login(): void
    {
        $role = Role::where('name', Role::ROLE_PEMOHON)->first();
        User::create([
            'name' => 'User Nonaktif',
            'email' => 'nonaktif@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $role->id,
            'is_active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'nonaktif@jmm.test',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * 5. Session regeneration setelah login.
     */
    public function test_session_id_is_regenerated_after_successful_login(): void
    {
        $role = Role::where('name', Role::ROLE_PEMOHON)->first();
        User::create([
            'name' => 'Siti Pemohon',
            'email' => 'siti@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $initialSessionId = session()->getId();

        $this->post('/login', [
            'email' => 'siti@jmm.test',
            'password' => 'Password123!',
        ]);

        $this->assertNotEquals($initialSessionId, session()->getId());
    }
}

