<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Disposition;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispositionTest extends TestCase
{
    use RefreshDatabase;

    protected User $pemohon;
    protected User $manager;
    protected User $adminFinance;
    protected Division $division;
    protected Category $category;
    protected BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        $pemohonRole = Role::where('name', 'pemohon')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $adminRole = Role::where('name', 'admin_finance')->first();

        $this->division = Division::create(['name' => 'Operasional', 'code' => 'OPS', 'is_active' => true]);
        $this->category = Category::create(['name' => 'BBM & Solar', 'code' => 'BBM', 'is_active' => true]);

        $bank = Bank::create(['name' => 'Bank Mandiri', 'code' => 'MANDIRI', 'is_active' => true]);
        $this->bankAccount = BankAccount::create([
            'owner_name' => 'Budi Santoso',
            'bank_id' => $bank->id,
            'bank_name' => 'Bank Mandiri',
            'account_number' => '0081234567890',
            'owner_type' => 'driver',
            'is_active' => true,
        ]);

        $this->pemohon = User::factory()->create([
            'name' => 'Pemohon Test',
            'email' => 'pemohon@test.com',
            'role_id' => $pemohonRole->id,
            'division_id' => $this->division->id,
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'name' => 'Manager Test',
            'email' => 'manager@test.com',
            'role_id' => $managerRole->id,
            'is_active' => true,
        ]);

        $this->adminFinance = User::factory()->create([
            'name' => 'Finance Test',
            'email' => 'finance@test.com',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_disposition_module(): void
    {
        $response = $this->get(route('dispositions.index'));
        $response->assertRedirect('/login');
    }

    public function test_pemohon_can_view_disposition_create_page(): void
    {
        $response = $this->actingAs($this->pemohon)->get(route('dispositions.create'));
        $response->assertStatus(200);
        $response->assertSee('Buat Pengajuan Disposisi');
        $response->assertSee('Budi Santoso');
    }

    public function test_pemohon_can_create_disposition_as_draft(): void
    {
        $postData = [
            'action' => 'draft',
            'disposition_date' => now()->toDateString(),
            'division_id' => $this->division->id,
            'subject' => 'Pengadaan BBM Armada Balikpapan',
            'description' => 'Untuk kebutuhan operasional armada rute BPN - SMD',
            'items' => [
                [
                    'item_name' => 'Solar Industri 500 Liter',
                    'category_id' => $this->category->id,
                    'quantity' => 500,
                    'unit' => 'Liter',
                    'unit_price' => 15000,
                    'amount' => 7500000,
                ],
            ],
            'recipients' => [
                [
                    'recipient_name' => 'Budi Santoso (Driver)',
                    'recipient_type' => 'driver',
                    'amount' => 7500000,
                    'primary_bank_account_id' => $this->bankAccount->id,
                    'primary_bank_name' => 'Bank Mandiri',
                    'primary_account_number' => '0081234567890',
                    'primary_account_holder' => 'Budi Santoso',
                ],
            ],
        ];

        $response = $this->actingAs($this->pemohon)->post(route('dispositions.store'), $postData);
        $response->assertSessionHasNoErrors();

        $disposition = Disposition::where('subject', 'Pengadaan BBM Armada Balikpapan')->first();
        $this->assertNotNull($disposition);
        $this->assertEquals(Disposition::STATUS_DRAFT, $disposition->approval_status);
        $this->assertEquals(7500000, (float) $disposition->requested_total);
        $this->assertCount(1, $disposition->items);
        $this->assertCount(1, $disposition->recipients);
    }

    public function test_pemohon_can_create_and_submit_disposition_directly(): void
    {
        $postData = [
            'action' => 'submit',
            'disposition_date' => now()->toDateString(),
            'division_id' => $this->division->id,
            'subject' => 'Pengadaan Sparepart Truk Tronton',
            'description' => 'Ganti oli dan ban tronton',
            'items' => [
                [
                    'item_name' => 'Ban Luar Bridgestone',
                    'category_id' => $this->category->id,
                    'quantity' => 2,
                    'unit' => 'Pcs',
                    'unit_price' => 2500000,
                    'amount' => 5000000,
                ],
            ],
            'recipients' => [
                [
                    'recipient_name' => 'Toko Ban Mandiri',
                    'recipient_type' => 'vendor',
                    'amount' => 5000000,
                    'primary_bank_account_id' => $this->bankAccount->id,
                    'primary_bank_name' => 'Bank Mandiri',
                    'primary_account_number' => '0081234567890',
                    'primary_account_holder' => 'Budi Santoso',
                ],
            ],
        ];

        $response = $this->actingAs($this->pemohon)->post(route('dispositions.store'), $postData);
        $response->assertSessionHasNoErrors();

        $disposition = Disposition::where('subject', 'Pengadaan Sparepart Truk Tronton')->first();
        $this->assertNotNull($disposition);
        $this->assertEquals(Disposition::STATUS_WAITING_MANAGER, $disposition->approval_status);
        $this->assertNotNull($disposition->disposition_number);
        $this->assertStringContainsString('JMM/', $disposition->disposition_number);
    }

    public function test_user_can_add_bank_account_in_place_via_ajax(): void
    {
        $ajaxData = [
            'owner_name' => 'Ahmad Suhendra',
            'bank_name' => 'BCA',
            'account_number' => '01499887766',
            'owner_type' => 'vendor',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->pemohon)
            ->postJson(route('master.bank-accounts.store'), $ajaxData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $created = BankAccount::where('account_number', '01499887766')->first();
        $this->assertNotNull($created);
        $this->assertEquals('01499887766', $created->account_number); // Leading zero preserved!
    }

    public function test_pemohon_cannot_view_others_disposition_in_show(): void
    {
        $otherPemohon = User::factory()->create([
            'role_id' => Role::where('name', 'pemohon')->first()->id,
            'is_active' => true,
        ]);

        $otherDisposition = Disposition::create([
            'disposition_number' => 'JMM/2026/0002',
            'applicant_id' => $otherPemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Disposisi Orang Lain',
            'requested_total' => 1000000,
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
        ]);

        $response = $this->actingAs($this->pemohon)->get(route('dispositions.show', $otherDisposition));
        $response->assertStatus(403);
    }

    public function test_manager_can_view_any_disposition(): void
    {
        $disposition = Disposition::create([
            'disposition_number' => 'JMM/2026/0003',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Disposisi untuk Manajer',
            'requested_total' => 2000000,
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
        ]);

        $response = $this->actingAs($this->manager)->get(route('dispositions.show', $disposition));
        $response->assertStatus(200);
        $response->assertSee('Disposisi untuk Manajer');
    }

    public function test_create_disposition_with_item_receipts_and_ppn_without_category(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $receiptOli = \Illuminate\Http\UploadedFile::fake()->create('nota_oli.jpg', 200, 'image/jpeg');
        $invoiceUtama = \Illuminate\Http\UploadedFile::fake()->create('invoice_utama.pdf', 500, 'application/pdf');

        $postData = [
            'action' => 'submit',
            'disposition_date' => now()->toDateString(),
            'division_id' => $this->division->id,
            'subject' => 'Pembelian Oli Mesin dan Sparepart Logistik',
            'description' => 'Disposisi dengan nota per item dan invoice utama',
            'ppn' => 110000,
            'items' => [
                [
                    'description' => 'Pembelian Oli Mesin Meditran SX',
                    'qty' => 10,
                    'unit' => 'Liter',
                    'amount' => 1000000,
                    'receipt' => $receiptOli,
                ],
            ],
            'recipients' => [
                [
                    'recipient_name' => 'Toko Sumber Oli',
                    'recipient_type' => 'vendor',
                    'amount' => 1110000,
                    'primary_bank_account_id' => $this->bankAccount->id,
                    'primary_bank_name' => 'Bank Mandiri',
                    'primary_account_number' => '0081234567890',
                    'primary_account_holder' => 'Budi Santoso',
                ],
            ],
            'attachments' => [$invoiceUtama],
        ];

        $response = $this->actingAs($this->pemohon)->post(route('dispositions.store'), $postData);
        $response->assertSessionHasNoErrors();

        $disposition = Disposition::where('subject', 'Pembelian Oli Mesin dan Sparepart Logistik')->first();
        $this->assertNotNull($disposition);
        // Total = 1.000.000 (items) + 110.000 (ppn) = 1.110.000
        $this->assertEquals(1110000, (float) $disposition->requested_total);
        $this->assertEquals(110000, (float) $disposition->ppn);

        // Check item has receipt path and name
        $item = $disposition->items->first();
        $this->assertNotNull($item->receipt_path);
        $this->assertEquals('nota_oli.jpg', $item->receipt_name);
        $this->assertEquals('Pembelian Oli Mesin Meditran SX', $item->description);
        $this->assertEquals(10, (float) $item->qty);
        $this->assertEquals('Liter', $item->unit);

        // Check global attachment is preserved
        $this->assertEquals(1, $disposition->attachments()->count());
    }

    public function test_admin_finance_can_delete_disposition(): void
    {
        $adminRole = Role::where('name', 'admin_finance')->first();
        $admin = User::factory()->create([
            'name' => 'Admin Fin',
            'email' => 'admin_fin@jmm.test',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $disp = Disposition::create([
            'disposition_number' => 'JMM/2026/0999',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Disposisi Yang Akan Dihapus Admin',
            'requested_total' => 500000,
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
        ]);

        $response = $this->actingAs($admin)->delete(route('dispositions.destroy', $disp));
        $response->assertRedirect(route('dispositions.index'));
        $this->assertDatabaseMissing('dispositions', ['id' => $disp->id]);
    }
}

