<?php

namespace Tests\Feature;

use App\Helpers\Terbilang;
use App\Models\Category;
use App\Models\Disposition;
use App\Models\Division;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAndReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Disposition $disposition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        $adminRole = Role::where('name', 'admin_finance')->first();
        $this->user = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $division = Division::create(['name' => 'Operasional', 'code' => 'OPS', 'is_active' => true]);
        $category = Category::create(['name' => 'BBM', 'code' => 'BBM', 'is_active' => true]);

        $this->disposition = Disposition::create([
            'disposition_number' => 'JMM/2026/0099',
            'disposition_date' => now()->toDateString(),
            'subject' => 'Pembelian Solar Logistik',
            'applicant_id' => $this->user->id,
            'division_id' => $division->id,
            'approval_status' => Disposition::STATUS_APPROVED,
            'payment_status' => Disposition::PAYMENT_PAID,
            'requested_total' => 5000000,
            'final_approved_total' => 5000000,
            'paid_total' => 5000000,
            'submitted_at' => now(),
        ]);

        $this->disposition->items()->create([
            'category_id' => $category->id,
            'description' => 'Solar Industri 500L',
            'amount' => 5000000,
        ]);

        $this->disposition->recipients()->create([
            'recipient_name' => 'PT Shell Indonesia',
            'recipient_type' => 'vendor',
            'primary_bank_name' => 'Bank Mandiri',
            'primary_account_number' => '1234567890',
            'primary_account_name' => 'PT Shell Indonesia',
            'allocated_amount' => 5000000,
        ]);
    }

    public function test_terbilang_helper_converts_nominal_correctly(): void
    {
        $this->assertEquals('Lima Juta Rupiah', Terbilang::make(5000000));
        $this->assertEquals('Satu Juta Dua Ratus Lima Puluh Ribu Rupiah', Terbilang::make(1250000));
        $this->assertEquals('Sepuluh Juta Rupiah', Terbilang::make(10000000));
        $this->assertEquals('Nol Rupiah', Terbilang::make(0));
    }

    public function test_user_can_stream_pdf_document(): void
    {
        $response = $this->actingAs($this->user)->get(route('dispositions.pdf', $this->disposition));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_user_can_view_print_version(): void
    {
        $response = $this->actingAs($this->user)->get(route('dispositions.print', $this->disposition));
        $response->assertStatus(200);
        $response->assertSee('FORMULIR DISPOSISI PENGELUARAN DANA');
        $response->assertSee('JMM/2026/0099');
    }

    public function test_user_can_view_image_format_document(): void
    {
        $response = $this->actingAs($this->user)->get(route('dispositions.image', $this->disposition));
        $response->assertStatus(200);
        $response->assertSee('FORMULIR DISPOSISI PENGELUARAN DANA');
        $response->assertSee('JMM/2026/0099');
        $response->assertSee('Unduh Gambar (JPG)');
    }

    public function test_user_can_view_reports_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Laporan Realisasi Disposisi');
        $response->assertSee('Total Pengajuan');
    }

    public function test_user_can_export_reports_to_excel(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.export-excel'));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    public function test_dashboard_renders_live_financial_stats_and_charts(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Selamat Datang, ' . $this->user->name);
        $response->assertSee('Tren Pengajuan vs Realisasi Pembayaran');
        $response->assertSee('chart-timeline');
    }
}

