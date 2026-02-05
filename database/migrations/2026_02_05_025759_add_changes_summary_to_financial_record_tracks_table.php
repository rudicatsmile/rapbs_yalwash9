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
        Schema::table('financial_record_tracks', function (Blueprint $table) {
            $table->json('changes_summary')->nullable()->after('snapshot_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_record_tracks', function (Blueprint $table) {
            $table->dropColumn('changes_summary');
        });
    }
};
