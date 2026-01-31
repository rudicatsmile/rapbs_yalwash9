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

beforeEach(function () {
    // Ensure roles exist
    Role::firstOrCreate(['name' => 'super_admin']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'user']);
    Role::firstOrCreate(['name' => 'editor']);
});

it('shows all records to admin', function () {
    // Create departments
    $deptA = Department::create(['name' => 'Dept A', 'urut' => 1]);
    $deptB = Department::create(['name' => 'Dept B', 'urut' => 2]);

    // Create admin user
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create records
    FinancialRecord::create([
        'user_id' => $admin->id,
        'department_id' => $deptA->id,
        'record_date' => now(),
        'record_name' => 'Record A',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
    ]);

    FinancialRecord::create([
        'user_id' => $admin->id,
        'department_id' => $deptB->id,
        'record_date' => now(),
        'record_name' => 'Record B',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
    ]);

    $this->actingAs($admin);

    Livewire::test(FinancialRecordsGridWidget::class)
        ->assertSee('Record A')
        ->assertSee('Record B');
});

it('shows only department records to department user', function () {
    // Create departments
    $deptA = Department::create(['name' => 'Dept A', 'urut' => 1]);
    $deptB = Department::create(['name' => 'Dept B', 'urut' => 2]);

    // Create user for Dept A
    $userA = User::factory()->create(['department_id' => $deptA->id]);
    // Note: Assuming regular users don't have 'admin' role
    $userA->assignRole('user'); 

    // Create records
    FinancialRecord::create([
        'user_id' => $userA->id,
        'department_id' => $deptA->id,
        'record_date' => now(),
        'record_name' => 'Record A',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
    ]);

    FinancialRecord::create([
        'user_id' => $userA->id, // Owner doesn't strictly matter for visibility rule, but good practice
        'department_id' => $deptB->id,
        'record_date' => now(),
        'record_name' => 'Record B',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
    ]);

    $this->actingAs($userA);

    Livewire::test(FinancialRecordsGridWidget::class)
        ->assertSee('Record A')
        ->assertDontSee('Record B');
});

it('shows all records to editor', function () {
    // Create departments
    $deptA = Department::create(['name' => 'Dept A', 'urut' => 1]);
    $deptB = Department::create(['name' => 'Dept B', 'urut' => 2]);

    // Create editor user
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    // Create records
    FinancialRecord::create([
        'user_id' => $editor->id,
        'department_id' => $deptA->id,
        'record_date' => now(),
        'record_name' => 'Record A',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
    ]);

    FinancialRecord::create([
        'user_id' => $editor->id,
        'department_id' => $deptB->id,
        'record_date' => now(),
        'record_name' => 'Record B',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
    ]);

    $this->actingAs($editor);

    Livewire::test(FinancialRecordsGridWidget::class)
        ->assertSee('Record A')
        ->assertSee('Record B');
});
