<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('realization_expense_lines')) {
            return;
        }

        Schema::create('realization_expense_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_record_id');
            $table->unsignedBigInteger('expense_item_id');
            $table->text('description');
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->decimal('realisasi', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['financial_record_id', 'expense_item_id'], 'rel_exp_lines_fr_ei_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realization_expense_lines');
    }
};
