<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->string('disposition_number')->nullable()->unique()->index();
            $table->date('disposition_date')->index();
            $table->string('subject');
            $table->foreignId('applicant_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->text('description')->nullable();
            
            // Status Approval & Pembayaran
            $table->string('approval_status')->default('draft')->index(); // draft, waiting_manager, waiting_finance, waiting_owner, revision_requested, rejected, approved
            $table->string('payment_status')->default('unpaid')->index(); // unpaid, partially_paid, paid
            
            // Nilai Keuangan Presisi Tinggi
            $table->decimal('requested_total', 18, 2)->default(0);
            $table->decimal('manager_approved_total', 18, 2)->nullable();
            $table->decimal('finance_verified_total', 18, 2)->nullable();
            $table->decimal('owner_approved_total', 18, 2)->nullable();
            $table->decimal('final_approved_total', 18, 2)->nullable();
            $table->decimal('paid_total', 18, 2)->default(0);
            
            $table->timestamp('submitted_at')->nullable()->index();
            $table->text('revision_notes')->nullable();
            $table->text('rejected_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('disposition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposition_id')->constrained('dispositions')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->text('description');
            $table->decimal('amount', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('disposition_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposition_id')->constrained('dispositions')->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_type')->default('employee'); // employee, driver, vendor, other
            
            // Rekening 1 (Wajib dari Client)
            $table->foreignId('primary_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('primary_account_name');
            $table->string('primary_bank_name');
            $table->string('primary_account_number');
            
            // Rekening 2 (Opsional dari Client)
            $table->foreignId('secondary_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('secondary_account_name')->nullable();
            $table->string('secondary_bank_name')->nullable();
            $table->string('secondary_account_number')->nullable();
            
            $table->decimal('allocated_amount', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposition_recipients');
        Schema::dropIfExists('disposition_items');
        Schema::dropIfExists('dispositions');
    }
};

