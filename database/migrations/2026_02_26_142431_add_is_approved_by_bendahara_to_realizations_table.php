<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financial_records', function (Blueprint $table) {
            $table->boolean('is_approved_by_bendahara')->default(false)->after('status_realisasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_records', function (Blueprint $table) {
            $table->dropColumn('is_approved_by_bendahara');
        });
    }
};
