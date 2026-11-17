<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update disposition_items
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('disposition_items', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });
        }

        Schema::table('disposition_items', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->decimal('qty', 12, 2)->default(1)->after('description');
            $table->string('unit', 50)->default('Pcs')->after('qty');
            $table->string('receipt_path')->nullable()->after('notes');
            $table->string('receipt_name')->nullable()->after('receipt_path');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('disposition_items', function (Blueprint $table) {
                $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            });
        }

        // 2. Update dispositions (tambah kolom ppn)
        Schema::table('dispositions', function (Blueprint $table) {
            $table->decimal('ppn', 18, 2)->default(0)->after('requested_total');
        });
    }

    public function down(): void
    {
        Schema::table('dispositions', function (Blueprint $table) {
            $table->dropColumn('ppn');
        });

        Schema::table('disposition_items', function (Blueprint $table) {
            $table->dropColumn(['qty', 'unit', 'receipt_path', 'receipt_name']);
        });
    }
};
