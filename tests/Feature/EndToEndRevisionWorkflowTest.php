<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CompanySetting;
use App\Models\Disposition;
use App\Models\DispositionApproval;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndRevisionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminFinance;
    protected User $manager;
    protected User $pemohon;
    protected User $owner;
    protected Division $division;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        $adminRole = Role::where('name', Role::ROLE_ADMIN_FINANCE)->first();
        $managerRole = Role::where('name', Role::ROLE_MANAGER)->first();
        $pemohonRole = Role::where('name', Role::ROLE_PEMOHON)->first();
        $ownerRole = Role::where('name', Role::ROLE_OWNER)->first();

        $this->division = Division::create(['name' => 'Operasional Balikpapan', 'code' => 'OPS-BPN', 'is_active' => true]);

        $this->adminFinance = User::create([
            'name' => 'Admin Keuangan',
            'email' => 'admin@jmmdisposisi.my.id',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Manager Operasional',
            'email' => 'manager@jmmdisposisi.my.id',
            'password' => Hash::make('password'),
            'role_id' => $managerRole->id,
            'is_active' => true,
        ]);

        $this->pemohon = User::create([
            'name' => 'Pemohon Lapangan',
            'email' => 'pemohon@jmmdisposisi.my.id',
            'password' => Hash::make('password'),
            'role_id' => $pemohonRole->id,
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Owner Utama',
            'email' => 'owner@jmmdisposisi.my.id',
            'password' => Hash::make('password'),
            'role_id' => $ownerRole->id,
            'is_active' => true,
        ]);
    }

    /**
     * Point 1: Perbaikan Kategori Pengeluaran (Bisa Dihapus)
     */
    public function test_point_1_category_can_be_deleted(): void
    {
        $category = Category::create([
            'name' => 'Kategori Sementara Yang Ingin Dihapus',
            'code' => 'KAT_DEL',
            'description' => 'Untuk diuji hapus',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminFinance)->delete(route('master.categories.destroy', $category));
        $response->assertRedirect(route('master.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    /**
     * Point 2, 4, 5, 6, 7:
     * - Rincian Pengeluaran tanpa kategori (Uraian, Qty, Satuan, Subtotal, PPN)
     * - Upload Nota per item dan Dokumen Pendukung Utama
     * - 2 Tahap Approval: Manager (ACC) -> Finance (ACC Akhir Sesuai Arahan Owner)
     * - Manager tidak bisa edit/hapus, Admin/Finance bisa edit/hapus
     */
    public function test_points_2_4_5_6_7_full_workflow(): void
    {
        Storage::fake('public');

        // 1. Buat Rekening Bank untuk Penerima
        $bankAccount = \App\Models\BankAccount::create([
            'owner_name' => 'CV Sumber Sparepart Jaya',
            'bank_name' => 'BCA',
            'account_number' => '01488776655',
            'owner_type' => 'vendor',
            'is_active' => true,
            'created_by' => $this->adminFinance->id,
        ]);

        // File nota item 1, item 2, dan file lampiran invoice utama
        $notaOli = UploadedFile::fake()->create('nota_oli.jpg', 300, 'image/jpeg');
        $notaFilter = UploadedFile::fake()->create('nota_filter.png', 250, 'image/png');
        $invoiceUtama = UploadedFile::fake()->create('invoice_resmi_po123.pdf', 600, 'application/pdf');

        // Form Submission Pengajuan Disposisi (Items tanpa category_id)
        $formData = [
            'action' => 'submit',
            'disposition_date' => now()->toDateString(),
            'division_id' => $this->division->id,
            'subject' => 'Pengadaan Pelumas dan Filter Truk Armada Balikpapan',
            'description' => 'Kebutuhan servis rutin 10 unit armada pengangkutan logistik',
            'ppn' => 125000,
            'items' => [
                [
                    'description' => 'Oli Mesin Meditran SX 15W-40',
                    'qty' => 10,
                    'unit' => 'Liter',
                    'amount' => 1000000,
                    'receipt' => $notaOli,
                ],
                [
                    'description' => 'Filter Oli Sakura C-1002',
                    'qty' => 2,
                    'unit' => 'Pcs',
                    'amount' => 250000,
                    'receipt' => $notaFilter,
                ],
            ],
            'recipients' => [
                [
                    'recipient_name' => 'CV Sumber Sparepart Jaya',
                    'recipient_type' => 'vendor',
                    'amount' => 1375000,
                    'primary_bank_account_id' => $bankAccount->id,
                    'primary_bank_name' => 'BCA',
                    'primary_account_number' => '01488776655',
                    'primary_account_holder' => 'CV Sumber Sparepart Jaya',
                ],
            ],
            'attachments' => [$invoiceUtama],
        ];

        // Pemohon submit disposisi
        $response = $this->actingAs($this->pemohon)->post(route('dispositions.store'), $formData);
        $response->assertSessionHasNoErrors();

        $disp = Disposition::where('subject', 'Pengadaan Pelumas dan Filter Truk Armada Balikpapan')->first();
        $this->assertNotNull($disp);

        // Validasi Nominal: Subtotal (1.000.000 + 250.000) + PPN (125.000) = 1.375.000
        $this->assertEquals(1375000, (float) $disp->requested_total);
        $this->assertEquals(125000, (float) $disp->ppn);
        $this->assertEquals(Disposition::STATUS_WAITING_MANAGER, $disp->approval_status);

        // Point 5 & 6: Validasi rincian items dan upload nota per item
        $this->assertCount(2, $disp->items);
        $item1 = $disp->items[0];
        $this->assertEquals('Oli Mesin Meditran SX 15W-40', $item1->description);
        $this->assertEquals(10, (float) $item1->qty);
        $this->assertEquals('Liter', $item1->unit);
        $this->assertEquals(1000000, (float) $item1->amount);
        $this->assertNotNull($item1->receipt_path);
        $this->assertEquals('nota_oli.jpg', $item1->receipt_name);

        $item2 = $disp->items[1];
        $this->assertEquals('Filter Oli Sakura C-1002', $item2->description);
        $this->assertEquals(2, (float) $item2->qty);
        $this->assertEquals('Pcs', $item2->unit);
        $this->assertEquals(250000, (float) $item2->amount);
        $this->assertNotNull($item2->receipt_path);
        $this->assertEquals('nota_filter.png', $item2->receipt_name);

        // Point 7: Lampiran Dokumen Pendukung Utama tetap ada
        $this->assertCount(1, $disp->attachments);
        $this->assertEquals('invoice_resmi_po123.pdf', $disp->attachments->first()->file_name);

        // Point 2: Hak akses hak edit & hapus
        // Manager TIDAK BISA edit maupun hapus
        $this->assertFalse($disp->canBeEditedBy($this->manager));
        $this->assertFalse($disp->canBeDeletedBy($this->manager));

        // Admin Finance BISA edit maupun hapus (Full access)
        $this->assertTrue($disp->canBeEditedBy($this->adminFinance));
        $this->assertTrue($disp->canBeDeletedBy($this->adminFinance));

        // Point 2 & 4: Tahap 1 Approval oleh Manager (ACC)
        $respMgr = $this->actingAs($this->manager)->post(route('approvals.decide', $disp), [
            'decision' => 'approved',
            'notes' => 'Kebutuhan oli dan filter sudah diverifikasi armada.',
        ]);
        $respMgr->assertRedirect(route('approvals.index'));

        $disp->refresh();
        $this->assertEquals(Disposition::STATUS_WAITING_FINANCE, $disp->approval_status);
        $this->assertEquals(1375000, (float) $disp->manager_approved_total);

        // Point 2 & 4: Tahap 2 (Akhir) Penentuan status akhir ACC oleh Finance berdasarkan arahan Owner
        $respFin = $this->actingAs($this->adminFinance)->post(route('approvals.decide', $disp), [
            'decision' => 'approved',
            'notes' => 'ACC final sesuai arahan langsung Owner via WhatsApp.',
        ]);
        $respFin->assertRedirect(route('approvals.index'));

        $disp->refresh();
        // Disposisi telah final disetujui (2 tahap selesai)
        $this->assertEquals(Disposition::STATUS_APPROVED, $disp->approval_status);
        $this->assertEquals(1375000, (float) $disp->final_approved_total);
        $this->assertEquals(2, $disp->approvals()->count());

        // Point 3: Format Output Disposisi JPG / Image
        $respImage = $this->actingAs($this->adminFinance)->get(route('dispositions.image', $disp));
        $respImage->assertStatus(200);
        $respImage->assertSee('FORMULIR DISPOSISI PENGELUARAN DANA');
        $respImage->assertSee('Unduh Gambar (JPG)');
        $respImage->assertSee('Salin Gambar (WA)');
        $respImage->assertSee('Oli Mesin Meditran SX 15W-40');
        $respImage->assertSee('Filter Oli Sakura C-1002');
        $respImage->assertSee('1.375.000');
        $respImage->assertSee('ACC MANAGER');
        $respImage->assertSee('ACC OWNER');
    }
}
