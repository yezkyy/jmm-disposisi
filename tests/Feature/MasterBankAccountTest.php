<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterBankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pemohon;
    protected Bank $bank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $adminRole = Role::where('name', Role::ROLE_ADMIN_FINANCE)->first();
        $pemohonRole = Role::where('name', Role::ROLE_PEMOHON)->first();

        $this->admin = User::create([
            'name' => 'Admin Keuangan',
            'email' => 'admin@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->pemohon = User::create([
            'name' => 'Pemohon Lapangan',
            'email' => 'pemohon@jmm.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $pemohonRole->id,
            'is_active' => true,
        ]);

        $this->bank = Bank::create([
            'name' => 'Bank Central Asia (BCA)',
            'code' => '014',
            'is_active' => true,
        ]);
    }

    /**
     * 12. CRUD Rekening: Buat rekening dengan leading zero utuh tersimpan sebagai string.
     */
    public function test_admin_can_create_bank_account_preserving_leading_zero(): void
    {
        $response = $this->actingAs($this->admin)->post(route('master.bank-accounts.store'), [
            'owner_name' => 'Bambang Supriyanto',
            'bank_id' => $this->bank->id,
            'bank_name' => $this->bank->name,
            'account_number' => '08812345678', // Nomor rekening dengan angka 0 di depan
            'owner_type' => BankAccount::TYPE_DRIVER,
            'owner_reference' => 'SOPIR-01',
            'notes' => 'Supir armada logistik',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('master.bank-accounts.index'));

        $account = BankAccount::where('owner_name', 'Bambang Supriyanto')->first();
        $this->assertNotNull($account);
        $this->assertSame('08812345678', $account->account_number); // Pastikan bukan 8812345678 (integer)
        $this->assertEquals($this->admin->id, $account->created_by);
    }

    /**
     * Update data rekening.
     */
    public function test_admin_can_update_bank_account(): void
    {
        $account = BankAccount::create([
            'owner_name' => 'Vendor Sparepart Lama',
            'bank_id' => $this->bank->id,
            'bank_name' => $this->bank->name,
            'account_number' => '00987654321',
            'owner_type' => BankAccount::TYPE_VENDOR,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('master.bank-accounts.update', $account), [
            'owner_name' => 'Vendor Sparepart Baru',
            'bank_id' => $this->bank->id,
            'bank_name' => $this->bank->name,
            'account_number' => '00987654321',
            'owner_type' => BankAccount::TYPE_VENDOR,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('master.bank-accounts.index'));
        $this->assertEquals('Vendor Sparepart Baru', $account->fresh()->owner_name);
    }

    /**
     * 14. Rekening nonaktif tidak dapat digunakan untuk transaksi baru (scope check).
     */
    public function test_inactive_bank_accounts_are_excluded_from_active_scope(): void
    {
        BankAccount::create([
            'owner_name' => 'Rekening Aktif',
            'bank_name' => 'BCA',
            'account_number' => '0123456789',
            'owner_type' => BankAccount::TYPE_EMPLOYEE,
            'is_active' => true,
        ]);

        BankAccount::create([
            'owner_name' => 'Rekening Nonaktif',
            'bank_name' => 'Mandiri',
            'account_number' => '0987654321',
            'owner_type' => BankAccount::TYPE_EMPLOYEE,
            'is_active' => false,
        ]);

        $activeAccounts = BankAccount::active()->pluck('owner_name')->toArray();

        $this->assertContains('Rekening Aktif', $activeAccounts);
        $this->assertNotContains('Rekening Nonaktif', $activeAccounts);
    }

    /**
     * 15. Pencegahan modifikasi rekening oleh role tanpa izin (Pemohon tidak boleh ubah/hapus rekening master).
     */
    public function test_pemohon_cannot_modify_master_bank_account(): void
    {
        $account = BankAccount::create([
            'owner_name' => 'Rekening Test',
            'bank_name' => 'BCA',
            'account_number' => '0111222333',
            'owner_type' => BankAccount::TYPE_EMPLOYEE,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->pemohon)->put(route('master.bank-accounts.update', $account), [
            'owner_name' => 'Hacked Owner Name',
            'bank_name' => 'BCA',
            'account_number' => '0111222333',
            'owner_type' => BankAccount::TYPE_EMPLOYEE,
            'is_active' => 1,
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Rekening Test', $account->fresh()->owner_name);
    }
}

