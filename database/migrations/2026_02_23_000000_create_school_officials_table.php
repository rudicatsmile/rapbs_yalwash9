<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_officials', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['role', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_officials');
    }
};

