<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispositions', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('disposition_number');
        });

        Schema::table('disposition_items', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('description');
            $table->decimal('qty', 12, 2)->nullable()->default(null)->change();
            $table->string('unit', 50)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('disposition_items', function (Blueprint $table) {
            $table->dropColumn('invoice_number');
        });

        Schema::table('dispositions', function (Blueprint $table) {
            $table->dropColumn('invoice_number');
        });
    }
};
