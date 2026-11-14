<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\CompanySetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CompanySettingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AuditAndBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $pemohon;
    protected User $adminFinance;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        $pemohonRole = Role::where('name', 'pemohon')->first();
        $adminRole = Role::where('name', 'admin_finance')->first();
        $ownerRole = Role::where('name', 'owner')->first();

        $this->pemohon = User::factory()->create([
            'role_id' => $pemohonRole->id,
            'is_active' => true,
        ]);

        $this->adminFinance = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'is_active' => true,
        ]);
    }

    public function test_pemohon_cannot_access_settings_audit_or_backup(): void
    {
        $this->actingAs($this->pemohon)->get(route('master.settings.index'))->assertStatus(403);
        $this->actingAs($this->pemohon)->get(route('master.audit.index'))->assertStatus(403);
        $this->actingAs($this->pemohon)->get(route('master.backups.index'))->assertStatus(403);
    }

    public function test_admin_can_update_company_settings(): void
    {
        $payload = [
            'company_name' => 'PT Jaya Mukti Mandiri Utama',
            'company_tagline' => 'Solusi Logistik & Alat Berat',
            'company_address' => 'Jl. Jenderal Sudirman No. 12, Balikpapan',
            'company_phone' => '(0542) 889900',
            'company_email' => 'finance.utama@jmm.co.id',
            'disposition_number_format' => 'JMM-OPS/{YYYY}/{NUMBER}',
            'disposition_number_digits' => 5,
        ];

        $response = $this->actingAs($this->adminFinance)->put(route('master.settings.update'), $payload);
        $response->assertRedirect(route('master.settings.index'));

        $this->assertEquals('PT Jaya Mukti Mandiri Utama', CompanySetting::get('company_name'));
        $this->assertEquals('JMM-OPS/{YYYY}/{NUMBER}', CompanySetting::get('disposition_number_format'));
        $this->assertEquals('5', CompanySetting::get('disposition_number_digits'));
    }

    public function test_admin_can_view_audit_logs(): void
    {
        AuditLog::record('test_action', null, ['foo' => 'bar'], ['foo' => 'baz']);

        $response = $this->actingAs($this->adminFinance)->get(route('master.audit.index'));
        $response->assertStatus(200);
        $response->assertSee('test_action');
    }

    public function test_admin_can_create_database_backup(): void
    {
        $response = $this->actingAs($this->adminFinance)->post(route('master.backups.store'), [
            'type' => 'sql',
        ]);

        $response->assertRedirect(route('master.backups.index'));
        $this->assertEquals(1, BackupLog::where('backup_type', BackupLog::TYPE_SQL)->count());

        $backup = BackupLog::where('backup_type', BackupLog::TYPE_SQL)->first();
        $this->assertEquals('completed', $backup->status);
        $this->assertTrue(File::exists(storage_path("app/{$backup->file_path}")));

        // Cleanup
        File::delete(storage_path("app/{$backup->file_path}"));
    }

    public function test_admin_can_create_full_disaster_recovery_backup_and_verify(): void
    {
        $response = $this->actingAs($this->adminFinance)->post(route('master.backups.store'), [
            'type' => 'zip',
        ]);

        $response->assertRedirect(route('master.backups.index'));

        $backup = BackupLog::where('backup_type', BackupLog::TYPE_ZIP)->latest('id')->first();
        $this->assertNotNull($backup);
        $this->assertEquals('completed', $backup->status);
        $this->assertTrue(File::exists(storage_path("app/{$backup->file_path}")));

        // Test verification endpoint
        $verifyResp = $this->actingAs($this->adminFinance)->post(route('master.backups.verify', $backup));
        $verifyResp->assertSessionHas('success');

        // Test download
        $downloadResp = $this->actingAs($this->adminFinance)->get(route('master.backups.download', $backup));
        $downloadResp->assertStatus(200);

        // Cleanup
        File::delete(storage_path("app/{$backup->file_path}"));
    }
}
