<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    /**
     * Uji halaman dashboard memuat komponen layout bagi pengguna terotentikasi.
     */
    public function test_dashboard_renders_base_layout_components(): void
    {
        $adminRole = Role::where('name', Role::ROLE_ADMIN_FINANCE)->first();
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);

        // Pastikan judul dan identitas perusahaan muncul
        $response->assertSee('JMM Disposisi');
        $response->assertSee('PT Jaya Mukti Mandiri');

        // Pastikan logo dimuat
        $response->assertSee('images/logo.png');

        // Pastikan komponen sidebar, topbar, dan footer dimuat
        $response->assertSee('Dashboard');
        $response->assertSee('Master Data');
        $response->assertSee('Sistem Aktif');
        $response->assertSee('Sistem Disposisi Pengeluaran');

        // Pastikan tombol logout tersedia
        $response->assertSee('logout');
    }
}
