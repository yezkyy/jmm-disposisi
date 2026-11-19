<?php

namespace Tests\Feature;

use App\Helpers\Terbilang;
use App\Models\DailyTransferProof;
use App\Models\Disposition;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RevisiVersi3Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pemohon;
    protected Division $division;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $adminRole = Role::where('name', 'admin_finance')->first();
        $pemohonRole = Role::where('name', 'pemohon')->first();

        $this->division = Division::first();

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->pemohon = User::factory()->create([
            'role_id' => $pemohonRole->id,
            'is_active' => true,
        ]);
    }

    public function test_point_1_and_4_logo_and_terbilang_accuracy(): void
    {
        // 67.500.000 harus menghasilkan Enam Puluh Tujuh Juta Lima Ratus Ribu Rupiah (bukan 6 Miliar)
        $this->assertEquals(
            'Enam Puluh Tujuh Juta Lima Ratus Ribu Rupiah',
            Terbilang::make('67500000.00')
        );
        $this->assertEquals(
            'Enam Juta Tujuh Ratus Delapan Puluh Dua Ribu Rupiah',
            Terbilang::make('6782000.00')
        );

        $disp = Disposition::create([
            'disposition_number' => 'JMM/2026/0099',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Pembelian Solar Industri',
            'requested_total' => 67500000,
            'approval_status' => Disposition::STATUS_APPROVED,
        ]);

        $disp->items()->create([
            'description' => 'Solar Industri 5000 Liter',
            'qty' => 5000,
            'unit' => 'Liter',
            'amount' => 67500000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dispositions.image', $disp));
        $response->assertStatus(200);
        $response->assertSee('Enam Puluh Tujuh Juta Lima Ratus Ribu Rupiah');
        $response->assertDontSee('Enam Miliar');
        // Kop surat memuat nama perusahaan dan logo
        $response->assertSee('PT Jaya Mukti Mandiri');
    }

    public function test_point_2_and_3_qty_whole_number_and_multiline_textarea(): void
    {
        // Form create harus menggunakan textarea untuk Uraian Kebutuhan dan step=1 untuk Qty
        $respCreate = $this->actingAs($this->pemohon)->get(route('dispositions.create'));
        $respCreate->assertStatus(200);
        $respCreate->assertSee('<textarea name="items[0][description]"', false);
        $respCreate->assertSee('step="1"', false);

        // Form edit juga harus menggunakan textarea dan menampilkan bilangan bulat
        $disp = Disposition::create([
            'disposition_number' => 'JMM/2026/0100',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Pengadaan Barang Logistik',
            'requested_total' => 1000000,
            'approval_status' => Disposition::STATUS_DRAFT,
        ]);

        $disp->items()->create([
            'description' => "Pengadaan Ban Truk Tronton\nUkuran 11.00-20\nUntuk armada balikpapan",
            'qty' => 10.00,
            'unit' => 'Unit',
            'amount' => 1000000,
        ]);

        $respEdit = $this->actingAs($this->pemohon)->get(route('dispositions.edit', $disp));
        $respEdit->assertStatus(200);
        $respEdit->assertSee('<textarea', false);
        $respEdit->assertSee('value="10"', false);
        $respEdit->assertDontSee('value="10.00"', false);
    }

    public function test_point_5_and_7_dashboard_and_index_daily_summaries(): void
    {
        // Buat disposisi hari ini
        Disposition::create([
            'disposition_number' => 'JMM/2026/0101',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Disposisi Hari Ini 1',
            'requested_total' => 2500000,
            'approval_status' => Disposition::STATUS_APPROVED,
        ]);

        Disposition::create([
            'disposition_number' => 'JMM/2026/0102',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Disposisi Hari Ini 2',
            'requested_total' => 1500000,
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
        ]);

        // Uji Dashboard Utama (Point 5)
        $respDashboard = $this->actingAs($this->admin)->get(route('dashboard'));
        $respDashboard->assertStatus(200);
        $respDashboard->assertSee('Ringkasan Aktivitas Disposisi Hari Ini');
        $respDashboard->assertSee('Rekapitulasi Disposisi Harian Berdasarkan Tanggal');
        $respDashboard->assertSee('2 Berkas'); // 2 disposisi hari ini

        // Uji Halaman Pengajuan Disposisi (Point 7)
        $respIndex = $this->actingAs($this->admin)->get(route('dispositions.index'));
        $respIndex->assertStatus(200);
        $respIndex->assertSee('Disposisi Diajukan Hari Ini');
        $respIndex->assertSee('Total Nominal Hari Ini');
        $respIndex->assertSee('Rp 4.000.000'); // 2.5jt + 1.5jt = 4jt
    }

    public function test_point_6_export_excel_and_pdf_reports(): void
    {
        Disposition::create([
            'disposition_number' => 'JMM/2026/0103',
            'applicant_id' => $this->pemohon->id,
            'disposition_date' => now()->toDateString(),
            'subject' => 'Disposisi Untuk Export',
            'requested_total' => 500000,
            'approval_status' => Disposition::STATUS_APPROVED,
        ]);

        // Export Excel
        $respExcel = $this->actingAs($this->admin)->get(route('reports.export-excel', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));
        $respExcel->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $respExcel->headers->get('Content-Type'));

        // Export PDF
        $respPdf = $this->actingAs($this->admin)->get(route('reports.export-pdf', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));
        $respPdf->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $respPdf->headers->get('Content-Type'));
    }

    public function test_point_8_daily_transfer_proof_workflow(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('bukti_transfer_gabungan.jpg', 800, 600);

        $postData = [
            'transfer_date' => now()->toDateString(),
            'title' => 'Transfer Gabungan BBM & Operasional',
            'notes' => 'Mencakup pengeluaran supir trip Samarinda',
            'file' => $file,
        ];

        $response = $this->actingAs($this->admin)->post(route('daily-transfer-proofs.store'), $postData);
        $response->assertRedirect();
        $response->assertSessionHas('toast');

        $proof = DailyTransferProof::first();
        $this->assertNotNull($proof);
        $this->assertEquals('Transfer Gabungan BBM & Operasional', $proof->title);
        $this->assertEquals(now()->toDateString(), $proof->transfer_date->toDateString());
        Storage::disk('public')->assertExists($proof->file_path);

        // Download proof
        $respDownload = $this->actingAs($this->admin)->get(route('daily-transfer-proofs.download', $proof));
        $respDownload->assertStatus(200);

        // Delete proof
        $respDelete = $this->actingAs($this->admin)->delete(route('daily-transfer-proofs.destroy', $proof));
        $respDelete->assertRedirect();
        $this->assertDatabaseMissing('daily_transfer_proofs', ['id' => $proof->id]);
        Storage::disk('public')->assertMissing($proof->file_path);
    }
}
