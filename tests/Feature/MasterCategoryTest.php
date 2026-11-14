<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterCategoryTest extends TestCase
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
     * 11. CRUD Kategori: Buat, edit, toggle status, dan hapus.
     */
    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin)->post(route('master.categories.store'), [
            'name' => 'BBM & Pelumas',
            'code' => 'BBM',
            'description' => 'Biaya bahan bakar armada',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('master.categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'BBM & Pelumas',
            'code' => 'BBM',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::create([
            'name' => 'Kategori Lama',
            'code' => 'KAT_OLD',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('master.categories.update', $category), [
            'name' => 'Kategori Baru',
            'code' => 'KAT_NEW',
            'description' => 'Deskripsi Baru',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('master.categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Kategori Baru',
            'code' => 'KAT_NEW',
        ]);
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $category = Category::create([
            'name' => 'Kategori Toggle',
            'code' => 'TOG',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('master.categories.toggle-status', $category));

        $response->assertSessionHas('success');
        $this->assertFalse((bool) $category->fresh()->is_active);
    }
}

