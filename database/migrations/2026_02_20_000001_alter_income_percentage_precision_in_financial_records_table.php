<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('financial_records')) {
            DB::statement('ALTER TABLE financial_records MODIFY income_percentage DECIMAL(15, 2)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('financial_records')) {
            DB::statement('ALTER TABLE financial_records MODIFY income_percentage DECIMAL(5, 2)');
        }
    }
};

