<?php

namespace Tests\Feature;

use App\Models\BankAccount;
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

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $pemohon;
    protected User $adminFinance;
    protected User $owner;
    protected Division $division;
    protected Category $category;
    protected BankAccount $sourceAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CompanySettingSeeder::class);

        $pemohonRole = Role::where('name', 'pemohon')->first();
        $adminRole = Role::where('name', 'admin_finance')->first();
        $ownerRole = Role::where('name', 'owner')->first();

        $this->division = Division::create(['name' => 'Operasional', 'code' => 'OPS', 'is_active' => true]);
        $this->category = Category::create(['name' => 'Solar & BBM', 'code' => 'BBM', 'is_active' => true]);

        $this->sourceAccount = BankAccount::create([
            'owner_name' => 'PT Jaya Mukti Mandiri',
            'bank_name' => 'Bank Mandiri',
            'account_number' => '1480098765432',
            'owner_type' => 'other',
            'is_active' => true,
        ]);

        $this->pemohon = User::factory()->create([
            'name' => 'Pemohon Lapangan',
            'email' => 'pemohon@jmm.test',
            'role_id' => $pemohonRole->id,
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

    protected function createApprovedDisposition(float $amount = 10000000): Disposition
    {
        $disp = Disposition::create([
            'disposition_number' => 'JMM/2026/0020',
            'disposition_date' => now()->toDateString(),
            'subject' => 'Pembelian Solar Industri 1000L',
            'applicant_id' => $this->pemohon->id,
            'division_id' => $this->division->id,
            'approval_status' => Disposition::STATUS_APPROVED,
            'payment_status' => Disposition::PAYMENT_UNPAID,
            'requested_total' => $amount,
            'final_approved_total' => $amount,
            'paid_total' => 0,
            'submitted_at' => now(),
        ]);

        $disp->items()->create([
            'category_id' => $this->category->id,
            'description' => 'Solar Industri',
            'amount' => $amount,
        ]);

        $disp->recipients()->create([
            'recipient_name' => 'PT Pertamina Patra Niaga',
            'recipient_type' => 'vendor',
            'primary_bank_name' => 'Bank Mandiri',
            'primary_account_number' => '0700012345678',
            'primary_account_name' => 'Pertamina Patra Niaga',
            'allocated_amount' => $amount,
        ]);

        return $disp;
    }

    public function test_pemohon_cannot_record_payment(): void
    {
        $disp = $this->createApprovedDisposition(5000000);

        $response = $this->actingAs($this->pemohon)->post(route('payments.store'), [
            'disposition_id' => $disp->id,
            'payment_date' => now()->toDateString(),
            'amount' => 5000000,
            'method' => 'transfer',
        ]);

        $response->assertStatus(403);
    }

    public function test_finance_can_record_full_payment(): void
    {
        $disp = $this->createApprovedDisposition(5000000);

        $response = $this->actingAs($this->adminFinance)->post(route('payments.store'), [
            'disposition_id' => $disp->id,
            'payment_date' => now()->toDateString(),
            'amount' => 5000000,
            'method' => 'transfer',
            'source_account_id' => $this->sourceAccount->id,
            'reference_number' => 'TRF-123456',
            'notes' => 'Pembayaran lunas via Mandiri',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, Payment::count());

        $payment = Payment::first();
        $this->assertEquals(5000000, (float) $payment->amount);
        $this->assertEquals(Payment::STATUS_POSTED, $payment->status);
        $this->assertStringContainsString('PAY/', $payment->payment_number);

        $disp->refresh();
        $this->assertEquals(5000000, (float) $disp->paid_total);
        $this->assertEquals(Disposition::PAYMENT_PAID, $disp->payment_status);
        $this->assertEquals(0, (float) $disp->remaining_payment);
    }

    public function test_partial_payments_accumulate_correctly(): void
    {
        $disp = $this->createApprovedDisposition(10000000);

        // Cicilan ke-1: Rp 4.000.000
        $this->actingAs($this->adminFinance)->post(route('payments.store'), [
            'disposition_id' => $disp->id,
            'payment_date' => now()->toDateString(),
            'amount' => 4000000,
            'method' => 'transfer',
            'source_account_id' => $this->sourceAccount->id,
        ]);

        $disp->refresh();
        $this->assertEquals(4000000, (float) $disp->paid_total);
        $this->assertEquals(Disposition::PAYMENT_PARTIALLY_PAID, $disp->payment_status);
        $this->assertEquals(6000000, (float) $disp->remaining_payment);

        // Cicilan ke-2: Rp 6.000.000 (Pelunasan)
        $this->actingAs($this->adminFinance)->post(route('payments.store'), [
            'disposition_id' => $disp->id,
            'payment_date' => now()->toDateString(),
            'amount' => 6000000,
            'method' => 'transfer',
            'source_account_id' => $this->sourceAccount->id,
        ]);

        $disp->refresh();
        $this->assertEquals(10000000, (float) $disp->paid_total);
        $this->assertEquals(Disposition::PAYMENT_PAID, $disp->payment_status);
        $this->assertEquals(0, (float) $disp->remaining_payment);
    }

    public function test_overpayment_is_strictly_rejected(): void
    {
        $disp = $this->createApprovedDisposition(5000000);

        // Mencoba membayar Rp 6.000.000 pada disposisi bernilai Rp 5.000.000
        $response = $this->actingAs($this->adminFinance)->post(route('payments.store'), [
            'disposition_id' => $disp->id,
            'payment_date' => now()->toDateString(),
            'amount' => 6000000,
            'method' => 'transfer',
        ]);

        $disp->refresh();
        $this->assertEquals(0, (float) $disp->paid_total);
        $this->assertEquals(0, Payment::count());
    }

    public function test_cannot_pay_unapproved_disposition(): void
    {
        $draftDisp = Disposition::create([
            'disposition_number' => 'JMM/2026/0021',
            'disposition_date' => now()->toDateString(),
            'subject' => 'Draft Disposisi',
            'applicant_id' => $this->pemohon->id,
            'approval_status' => Disposition::STATUS_WAITING_MANAGER,
            'requested_total' => 3000000,
        ]);

        $response = $this->actingAs($this->adminFinance)->post(route('payments.store'), [
            'disposition_id' => $draftDisp->id,
            'payment_date' => now()->toDateString(),
            'amount' => 3000000,
            'method' => 'transfer',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Payment::count());
    }

    public function test_finance_can_reverse_payment_and_restore_disposition_status(): void
    {
        $disp = $this->createApprovedDisposition(5000000);

        // Bayar lunas
        $this->actingAs($this->adminFinance)->post(route('payments.store'), [
            'disposition_id' => $disp->id,
            'payment_date' => now()->toDateString(),
            'amount' => 5000000,
            'method' => 'transfer',
            'source_account_id' => $this->sourceAccount->id,
        ]);

        $disp->refresh();
        $this->assertEquals(Disposition::PAYMENT_PAID, $disp->payment_status);

        $payment = Payment::first();

        // Lakukan pembatalan (Reversal)
        $response = $this->actingAs($this->adminFinance)->post(route('payments.reverse', $payment), [
            'reversal_reason' => 'Kesalahan nomor rekening transfer, dana ditarik kembali.',
        ]);

        $response->assertRedirect(route('payments.show', $payment));

        $payment->refresh();
        $this->assertEquals(Payment::STATUS_REVERSED, $payment->status);
        $this->assertEquals('Kesalahan nomor rekening transfer, dana ditarik kembali.', $payment->reversal_reason);
        $this->assertEquals($this->adminFinance->id, $payment->reversed_by);

        // Status disposisi pulih kembali ke UNPAID dan paid_total menjadi 0
        $disp->refresh();
        $this->assertEquals(0, (float) $disp->paid_total);
        $this->assertEquals(Disposition::PAYMENT_UNPAID, $disp->payment_status);
        $this->assertEquals(5000000, (float) $disp->remaining_payment);
    }

    /**
     * Memastikan rekening sumber hanya rekening PT JMM dan penerima pembayaran dapat dipilih dari master bank.
     */
    public function test_source_accounts_only_company_and_recipients_can_be_selected_from_master(): void
    {
        $disp = $this->createApprovedDisposition(2000000);

        // Buat akun rekening vendor/sopir di master bank
        $driverAccount = BankAccount::create([
            'owner_name' => 'Dede Komara',
            'bank_name' => 'Bank Rakyat Indonesia (BRI)',
            'account_number' => '479301017890534',
            'owner_type' => BankAccount::TYPE_DRIVER,
            'is_active' => true,
        ]);

        // Cek halaman pembayaran: rekening Dede Komara TIDAK boleh muncul di Rekening Sumber PT JMM
        $response = $this->actingAs($this->adminFinance)->get(route('payments.create', ['disposition_id' => $disp->id]));
        $response->assertStatus(200);

        // Dede Komara harus ada di opsi Penerima Pembayaran
        $response->assertSee('value="acc_' . $driverAccount->id . '"', false);
        $response->assertSee('Dede Komara &bull; Bank Rakyat Indonesia (BRI)', false);

        // Rekening PT JMM ada di Rekening Sumber
        $response->assertSee('value="' . $this->sourceAccount->id . '"', false);

        // Bayar dengan memilih Dede Komara dari master rekening via recipient_select="acc_ID"
        $postResp = $this->actingAs($this->adminFinance)->post(route('payments.store'), [
            'disposition_id' => $disp->id,
            'payment_date' => now()->toDateString(),
            'amount' => 2000000,
            'method' => 'transfer',
            'source_account_id' => $this->sourceAccount->id,
            'recipient_select' => 'acc_' . $driverAccount->id,
        ]);

        $postResp->assertRedirect();
        $payment = Payment::latest()->first();
        $this->assertNotNull($payment->recipient);
        $this->assertEquals('Dede Komara', $payment->recipient->recipient_name);
        $this->assertEquals('479301017890534', $payment->recipient->primary_account_number);
    }
}

