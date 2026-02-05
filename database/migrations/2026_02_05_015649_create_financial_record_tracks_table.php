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
        Schema::create('financial_record_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_record_id')->constrained('financial_records')->onDelete('cascade');
            $table->unsignedInteger('version');
            $table->json('snapshot_data');
            $table->enum('action_type', ['INITIAL_FINAL', 'UPDATE_FINAL']);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_record_tracks');
    }
};
