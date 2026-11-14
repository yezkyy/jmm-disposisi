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
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $pemohon;
    protected User $manager;
    protected User $adminFinance;
    protected User $owner;
    protected Division $division;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        $pemohonRole = Role::where('name', 'pemohon')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $adminRole = Role::where('name', 'admin_finance')->first();
        $ownerRole = Role::where('name', 'owner')->first();

        $this->division = Division::create(['name' => 'Operasional', 'code' => 'OPS', 'is_active' => true]);
        $this->category = Category::create(['name' => 'Logistik', 'code' => 'LOG', 'is_active' => true]);

        $this->pemohon = User::factory()->create([
            'name' => 'Pemohon Lapangan',
            'email' => 'pemohon@jmm.test',
            'role_id' => $pemohonRole->id,
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'name' => 'Manager Operasional',
            'email' => 'manager@jmm.test',
            'role_id' => $managerRole->id,
            'is_active' => true,
        ]);

        $this->adminFinance = User::factory()->create([
            'name' => 'Admin Finance',
            'email' => 'finance@jmm.test',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'name' => 'Owner Direktur',
            'email' => 'owner@jmm.test',
            'role_id' => $ownerRole->id,
            'is_active' => true,
        ]);
    }

    protected function createActiveDisposition(float $amount = 10000000): Disposition
    {
        $disp = Disposition::create([
            'disposition_number' => 'JMM/2026/0010',
            'disposition_date' => now()->toDateString(),
            'subject' => 'Pembelian Pelumas Alat Berat',
            'applicant_id' => $this->pemohon->id,
            'division_id' => $this->division->id,
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
            'payment_status' => Disposition::PAYMENT_UNPAID,
            'requested_total' => $amount,
            'submitted_at' => now(),
        ]);

        $disp->items()->create([
            'category_id' => $this->category->id,
            'description' => 'Pelumas Drum Shell',
            'amount' => $amount,
        ]);

        $disp->recipients()->create([
            'recipient_name' => 'PT Shell Indonesia',
            'recipient_type' => 'vendor',
            'primary_bank_name' => 'BCA',
            'primary_account_number' => '0123456789',
            'primary_account_name' => 'PT Shell Indonesia',
            'allocated_amount' => $amount,
        ]);

        return $disp;
    }

    public function test_pemohon_cannot_access_approval_inbox(): void
    {
        $response = $this->actingAs($this->pemohon)->get(route('approvals.index'));
        $response->assertStatus(403);
    }

    public function test_full_sequential_approval_progression(): void
    {
        $disp = $this->createActiveDisposition(5000000);

        // 1. Manager Review & Full Approval
        $respManager = $this->actingAs($this->manager)->post(route('approvals.decide', $disp), [
            'decision' => 'approved',
            'notes' => 'Disetujui untuk kebutuhan mendesak.',
        ]);
        $respManager->assertRedirect(route('approvals.index'));

        $disp->refresh();
        $this->assertEquals(Disposition::STATUS_WAITING_FINANCE, $disp->approval_status);
        $this->assertEquals(5000000, (float) $disp->manager_approved_total);

        // 2. Finance Verification
        $respFinance = $this->actingAs($this->adminFinance)->post(route('approvals.decide', $disp), [
            'decision' => 'approved',
            'notes' => 'Kelengkapan dokumen dan anggaran valid.',
        ]);
        $respFinance->assertRedirect(route('approvals.index'));

        $disp->refresh();
        $this->assertEquals(Disposition::STATUS_WAITING_OWNER, $disp->approval_status);
        $this->assertEquals(5000000, (float) $disp->finance_verified_total);

        // 3. Owner Final Approval
        $respOwner = $this->actingAs($this->owner)->post(route('approvals.decide', $disp), [
            'decision' => 'approved',
            'notes' => 'Persetujuan akhir direktur.',
        ]);
        $respOwner->assertRedirect(route('approvals.index'));

        $disp->refresh();
        $this->assertEquals(Disposition::STATUS_APPROVED, $disp->approval_status);
        $this->assertEquals(5000000, (float) $disp->final_approved_total);

        // Verifikasi tabel disposition_approvals
        $this->assertEquals(3, $disp->approvals()->count());
    }

    public function test_self_approval_is_strictly_forbidden(): void
    {
        // Manager mengajukan disposisi sendiri
        $disp = Disposition::create([
            'disposition_number' => 'JMM/2026/0011',
            'disposition_date' => now()->toDateString(),
            'subject' => 'Pengajuan Pribadi Manager',
            'applicant_id' => $this->manager->id,
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
            'requested_total' => 2000000,
            'submitted_at' => now(),
        ]);

        $disp->items()->create([
            'category_id' => $this->category->id,
            'description' => 'Biaya dinas',
            'amount' => 2000000,
        ]);

        // Manager mencoba melakukan approval terhadap pengajuannya sendiri
        $response = $this->actingAs($this->manager)->post(route('approvals.decide', $disp), [
            'decision' => 'approved',
            'notes' => 'Saya setujui pengajuan saya sendiri',
        ]);

        // Harus gagal dengan session error
        $response->assertSessionHas('error');
        $disp->refresh();
        $this->assertEquals(Disposition::STATUS_WAITING_MANAGER, $disp->approval_status);
    }

    public function test_partial_approval_updates_amounts_and_requires_notes(): void
    {
        $disp = $this->createActiveDisposition(10000000);

        // Manager menyetujui sebagian (Rp 7.000.000)
        $respManager = $this->actingAs($this->manager)->post(route('approvals.decide', $disp), [
            'decision' => 'partially_approved',
            'approved_amount' => 7000000,
            'notes' => 'Disetujui sebagian karena stok sebagian masih tersedia.',
        ]);
        $respManager->assertRedirect(route('approvals.index'));

        $disp->refresh();
        $this->assertEquals(Disposition::STATUS_WAITING_FINANCE, $disp->approval_status);
        $this->assertEquals(7000000, (float) $disp->manager_approved_total);

        // Finance verifikasi
        $this->actingAs($this->adminFinance)->post(route('approvals.decide', $disp), [
            'decision' => 'approved',
            'notes' => 'Verifikasi finance sesuai persetujuan manager.',
        ]);

        // Owner final partial approval (Rp 6.000.000)
        $respOwner = $this->actingAs($this->owner)->post(route('approvals.decide', $disp), [
            'decision' => 'partially_approved',
            'approved_amount' => 6000000,
            'notes' => 'Dipotong Rp 1.000.000 untuk efisiensi cashflow bulan ini.',
        ]);
        $respOwner->assertRedirect(route('approvals.index'));

        $disp->refresh();
        $this->assertEquals(Disposition::STATUS_PARTIALLY_APPROVED, $disp->approval_status);
        $this->assertEquals(6000000, (float) $disp->final_approved_total);
    }

    public function test_revision_requested_returns_to_applicant(): void
    {
        $disp = $this->createActiveDisposition(3000000);

        // Manager minta revisi
        $response = $this->actingAs($this->manager)->post(route('approvals.decide', $disp), [
            'decision' => 'revision_requested',
            'notes' => 'Mohon lampirkan rincian invoice pembanding vendor.',
        ]);
        $response->assertRedirect(route('approvals.index'));

        $disp->refresh();
        $this->assertEquals(Disposition::STATUS_REVISION_REQUESTED, $disp->approval_status);
        $this->assertEquals('Mohon lampirkan rincian invoice pembanding vendor.', $disp->revision_notes);
        $this->assertTrue($disp->canBeEditedBy($this->pemohon));
    }

    public function test_rejection_is_terminal(): void
    {
        $disp = $this->createActiveDisposition(4000000);

        // Manager tolak pengajuan
        $response = $this->actingAs($this->manager)->post(route('approvals.decide', $disp), [
            'decision' => 'rejected',
            'notes' => 'Pengadaan tidak disetujui karena tidak sesuai Rencana Anggaran Biaya.',
        ]);
        $response->assertRedirect(route('approvals.index'));

        $disp->refresh();
        $this->assertEquals(Disposition::STATUS_REJECTED, $disp->approval_status);
        $this->assertFalse($disp->canBeEditedBy($this->pemohon));
    }
}

