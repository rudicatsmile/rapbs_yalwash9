<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            if (!Schema::hasColumn('expense_items', 'realisasi')) {
                $table->decimal('realisasi', 15, 2)->default(0)->after('source_type');
            }
            if (!Schema::hasColumn('expense_items', 'saldo')) {
                $table->decimal('saldo', 15, 2)->default(0)->after('realisasi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_items', function (Blueprint $table) {
            $table->dropColumn(['realisasi', 'saldo']);
        });
    }
};
