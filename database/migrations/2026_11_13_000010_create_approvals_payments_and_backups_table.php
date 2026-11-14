<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposition_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposition_id')->constrained('dispositions')->cascadeOnDelete();
            $table->string('stage')->index(); // manager, finance, owner
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('decision')->index(); // approved, partially_approved, revision_requested, rejected
            $table->decimal('previous_amount', 18, 2)->nullable();
            $table->decimal('approved_amount', 18, 2)->nullable();
            $table->text('notes')->nullable();
            $table->integer('revision_round')->default(1);
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposition_id')->constrained('dispositions')->restrictOnDelete();
            $table->string('payment_number')->unique()->index();
            $table->date('payment_date')->index();
            $table->decimal('amount', 18, 2);
            $table->string('method')->default('transfer'); // transfer, cash, other
            $table->foreignId('source_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('disposition_recipients')->nullOnDelete();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('proof_name')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('posted')->index(); // posted, reversed
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type')->index();
            $table->unsignedBigInteger('attachable_id')->index();
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('backup_type')->index(); // sql, excel, attachments, zip
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status')->default('completed')->index(); // completed, failed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('disposition_approvals');
    }
};

