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
        Schema::create('realization_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_record_id')->constrained('financial_records')->onDelete('cascade');
            $table->unsignedInteger('version');
            $table->json('snapshot_data');
            $table->json('changes_summary')->nullable();
            $table->enum('action_type', ['INITIAL_REALIZATION', 'UPDATE_REALIZATION']);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realization_tracks');
    }
};
