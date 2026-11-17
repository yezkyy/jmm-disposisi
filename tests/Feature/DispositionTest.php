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

    public function test_store_disposition_without_qty_and_unit_and_with_invoice_number(): void
    {
        $postData = [
            'action' => 'submit',
            'disposition_date' => now()->toDateString(),
            'division_id' => $this->division->id,
            'subject' => 'Operasional Tambal Ban dan Uang Jalan',
            'invoice_number' => 'INV/OPERASIONAL/2026/01',
            'items' => [
                [
                    'invoice_number' => 'INV/OPERASIONAL/2026/01',
                    'description' => 'Tambal ban mobil jmm armada logistik',
                    'qty' => '', // kosong / tidak diisi
                    'unit' => '', // kosong / tidak diisi
                    'amount' => 155000,
                ],
                [
                    'invoice_number' => null,
                    'description' => 'Uang makan supir tambahan',
                    'qty' => null,
                    'unit' => null,
                    'amount' => 100000,
                ],
            ],
            'recipients' => [
                [
                    'recipient_name' => 'Bambang Supir',
                    'recipient_type' => 'driver',
                    'primary_bank_account_id' => $this->bankAccount->id,
                    'notes' => 'Operasional Balikpapan',
                ],
            ],
        ];

        $response = $this->actingAs($this->pemohon)->post(route('dispositions.store'), $postData);
        $response->assertSessionHasNoErrors();

        $disp = Disposition::where('subject', 'Operasional Tambal Ban dan Uang Jalan')->first();
        $this->assertNotNull($disp);
        $this->assertEquals('INV/OPERASIONAL/2026/01', $disp->invoice_number);
        $this->assertEquals(255000, (float) $disp->requested_total);

        $items = $disp->items()->orderBy('id')->get();
        $this->assertCount(2, $items);
        $this->assertNull($items[0]->qty);
        $this->assertNull($items[0]->unit);
        $this->assertEquals('INV/OPERASIONAL/2026/01', $items[0]->invoice_number);

        $this->assertNull($items[1]->qty);
        $this->assertNull($items[1]->unit);
        $this->assertNull($items[1]->invoice_number);
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

    public function test_disposition_number_generation_skips_existing_numbers_without_duplicate_error(): void
    {
        // Simulasi kondisi riil: tabel dispositions sudah ada nomor JMM/2026/0001
        // tetapi tabel disposition_sequences belum ada atau last_number = 0
        Disposition::create([
            'disposition_number' => 'JMM/2026/0001',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => '2026-11-17',
            'subject' => 'Disposisi Lama Nomor 0001',
            'requested_total' => 1000000,
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
        ]);

        $postData = [
            'action' => 'submit',
            'disposition_date' => '2026-11-17',
            'division_id' => $this->division->id,
            'subject' => 'Pengajuan Disposisi Baru',
            'items' => [
                [
                    'description' => 'Biaya BBM Kapal',
                    'qty' => 1,
                    'unit' => 'Bulan',
                    'amount' => 500000,
                ],
            ],
            'recipients' => [
                [
                    'recipient_name' => 'PT Pertamina',
                    'recipient_type' => 'vendor',
                    'primary_bank_account_id' => $this->bankAccount->id,
                    'notes' => 'KCP Balikpapan Sudirman',
                ],
            ],
        ];

        // Pastikan tidak melempar Exception Duplicate Entry 1062
        $response = $this->actingAs($this->pemohon)->post(route('dispositions.store'), $postData);
        $response->assertRedirect();

        $newDisp = Disposition::where('subject', 'Pengajuan Disposisi Baru')->first();
        $this->assertNotNull($newDisp);
        $this->assertEquals('JMM/2026/0002', $newDisp->disposition_number);
    }

    public function test_deleting_disposition_with_payment_records_succeeds_without_foreign_key_error(): void
    {
        $adminRole = Role::where('name', 'admin_finance')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $disp = Disposition::create([
            'disposition_number' => 'JMM/2026/0050',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Disposisi dengan Pembayaran yang akan dihapus',
            'requested_total' => 1500000,
            'approval_status' => Disposition::STATUS_APPROVED,
        ]);

        $recipient = $disp->recipients()->create([
            'recipient_name' => 'Rekanan Toko Logistik',
            'primary_account_name' => 'Toko Logistik',
            'primary_bank_name' => 'BCA',
            'primary_account_number' => '88990011',
            'notes' => 'Cabang Balikpapan Baru',
        ]);

        // Tambahkan record payment yang sebelumnya menyebabkan SQL 1451 FK constraint fails
        \App\Models\Payment::create([
            'disposition_id' => $disp->id,
            'payment_number' => 'PAY/2026/11/9999',
            'payment_date' => now()->toDateString(),
            'amount' => 1500000,
            'method' => 'transfer',
            'source_account_id' => $this->bankAccount->id,
            'recipient_id' => $recipient->id,
            'recorded_by' => $admin->id,
            'status' => 'posted',
        ]);

        // Hapus disposisi
        $response = $this->actingAs($admin)->delete(route('dispositions.destroy', $disp));
        $response->assertRedirect(route('dispositions.index'));

        // Pastikan data disposisi dan payment terhapus bersih tanpa error
        $this->assertDatabaseMissing('dispositions', ['id' => $disp->id]);
        $this->assertDatabaseMissing('payments', ['payment_number' => 'PAY/2026/11/9999']);
    }

    public function test_recipient_keterangan_saved_and_image_shows_clean_pending_owner_approval(): void
    {
        $disp = Disposition::create([
            'disposition_number' => 'JMM/2026/0088',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Disposisi Pengadaan Solar',
            'requested_total' => 10000000,
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
        ]);

        $disp->recipients()->create([
            'recipient_name' => 'PT Pertamina Patra Niaga',
            'recipient_type' => 'vendor',
            'primary_account_name' => 'PT Pertamina Patra Niaga',
            'primary_bank_name' => 'Bank Mandiri',
            'primary_account_number' => '0700012345678',
            'notes' => 'KCP Sudirman Balikpapan',
        ]);

        $disp->items()->create([
            'description' => 'Solar Industri 1000L',
            'qty' => 1000,
            'unit' => 'Liter',
            'amount' => 10000000,
        ]);

        $response = $this->actingAs($this->pemohon)->get(route('dispositions.image', $disp));
        $response->assertStatus(200);

        // Kolom rekening harus menampilkan Keterangan / Cabang bank
        $response->assertSee('Keterangan');
        $response->assertSee('KCP Sudirman Balikpapan');

        // Alur persetujuan harus sesuai format:
        $response->assertSee('1. Diajukan oleh (Admin/Finance)');
        $response->assertSee('2. Persetujuan Manager');
        $response->assertSee('3. Keputusan Akhir Finance (Sesuai Arahan Owner)');
        $response->assertSee('( Menunggu Persetujuan Manager )');
        $response->assertDontSee('ACC OWNER');
        // Kolom nota / bukti tidak ditampilkan pada format gambar
        $response->assertDontSee('Nota / Bukti');
    }

    public function test_optional_invoice_number_and_dynamic_qty_unit_rendering(): void
    {
        // Kasus 1: Disposisi operasional tanpa nomor invoice & tanpa Qty/Satuan (misal uang jalan supir)
        $dispOperational = Disposition::create([
            'disposition_number' => 'JMM/2026/0089',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Uang Jalan dan Operasional Armada',
            'requested_total' => 500000,
            'approval_status' => Disposition::STATUS_APPROVED,
        ]);

        $dispOperational->recipients()->create([
            'recipient_name' => 'Bambang Supir',
            'recipient_type' => 'driver',
            'primary_account_name' => 'Bambang',
            'primary_bank_name' => 'BCA',
            'primary_account_number' => '1234567890',
            'notes' => 'Cabang Samarinda',
        ]);

        $dispOperational->items()->create([
            'description' => 'Uang jalan armada logistik tujuan Samarinda',
            'qty' => null,
            'unit' => null,
            'amount' => 500000,
        ]);

        $respOp = $this->actingAs($this->pemohon)->get(route('dispositions.image', $dispOperational));
        $respOp->assertStatus(200);

        // Pada format gambar, karena tidak ada qty & satuan, kolom Qty dan Satuan TIDAK boleh muncul
        $respOp->assertDontSee('<th class="py-2 px-2 text-right border-r border-slate-300 w-16">Qty</th>', false);
        $respOp->assertDontSee('<th class="py-2 px-2 text-center border-r border-slate-300 w-20">Satuan</th>', false);
        // Karena tidak ada nomor invoice, No. Invoice tidak muncul di header metadata
        $respOp->assertDontSee('No. Invoice');

        // Kasus 2: Disposisi dengan No Invoice & dengan Qty/Satuan (pengadaan barang)
        $dispPurchase = Disposition::create([
            'disposition_number' => 'JMM/2026/0090',
            'invoice_number' => 'INV-JMM-2026-99',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Pengadaan Sparepart Truk',
            'requested_total' => 1250000,
            'approval_status' => Disposition::STATUS_APPROVED,
        ]);

        $dispPurchase->recipients()->create([
            'recipient_name' => 'Toko Sparepart Jaya',
            'recipient_type' => 'vendor',
            'primary_account_name' => 'Toko Sparepart Jaya',
            'primary_bank_name' => 'Bank Mandiri',
            'primary_account_number' => '9876543210',
            'notes' => 'Invoice INV-JMM-2026-99',
        ]);

        $dispPurchase->items()->create([
            'invoice_number' => 'INV-JMM-2026-99',
            'description' => 'Kampas Rem Truk Fuso',
            'qty' => 4,
            'unit' => 'Set',
            'amount' => 1250000,
        ]);

        $respPurchase = $this->actingAs($this->pemohon)->get(route('dispositions.image', $dispPurchase));
        $respPurchase->assertStatus(200);

        // Header Invoice muncul
        $respPurchase->assertSee('No. Invoice');
        $respPurchase->assertSee('INV-JMM-2026-99');

        // Kolom Qty dan Satuan harus muncul
        $respPurchase->assertSee('Qty');
        $respPurchase->assertSee('Satuan');
        $respPurchase->assertSee('4');
        $respPurchase->assertSee('Set');
    }
}

