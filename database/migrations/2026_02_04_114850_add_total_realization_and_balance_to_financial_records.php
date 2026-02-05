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
        Schema::table('financial_records', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_records', 'total_realization')) {
                $table->decimal('total_realization', 15, 2)->default(0)->after('total_expense');
            }
            if (!Schema::hasColumn('financial_records', 'total_balance')) {
                $table->decimal('total_balance', 15, 2)->default(0)->after('total_realization');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_records', function (Blueprint $table) {
            $table->dropColumn(['total_realization', 'total_balance']);
        });
    }
};
