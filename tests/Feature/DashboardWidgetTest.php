<?php

namespace Tests\Feature;

use App\Filament\Widgets\FinancialRecordsGridWidget;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('displays financial records grid on dashboard', function () {
    // Create department and user
    $department = Department::create([
        'name' => 'Finance',
        'urut' => 1,
        'description' => 'Finance Dept',
    ]);

    $user = User::factory()->create([
        'department_id' => $department->id,
    ]);

    // Create records
    FinancialRecord::create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'record_date' => now()->format('Y-m-d'),
        'record_name' => 'Test Income',
        'income_amount' => 1000000,
        'income_percentage' => 100,
        'income_fixed' => 1000000,
        'total_expense' => 500000,
    ]);

    // Mock auth
    $this->actingAs($user);

    // Test widget
    Livewire::test(FinancialRecordsGridWidget::class)
        ->assertSee('Finance')
        ->assertSee('Test Income')
        ->assertSee('Pemasukan')
        ->assertSee('Pengeluaran');
});
