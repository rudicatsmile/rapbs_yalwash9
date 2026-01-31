<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\EditFinancialRecord;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure roles exist
    Role::firstOrCreate(['name' => 'super_admin']);
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'user']);
    Role::firstOrCreate(['name' => 'editor']);

    // Ensure permissions exist
    Permission::firstOrCreate(['name' => 'Update:FinancialRecord']);
    Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord']);
    Permission::firstOrCreate(['name' => 'View:FinancialRecord']);
});

it('allows admin to edit inactive record', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->givePermissionTo(['Update:FinancialRecord', 'ViewAny:FinancialRecord', 'View:FinancialRecord']);

    $dept = Department::create(['name' => 'Test Dept']);

    $record = FinancialRecord::create([
        'user_id' => $admin->id,
        'department_id' => $dept->id,
        'record_date' => now(),
        'record_name' => 'Inactive Record',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
        'status' => false, // Inactive
    ]);

    Livewire::actingAs($admin)
        ->test(EditFinancialRecord::class, ['record' => $record->getRouteKey()])
        ->assertSuccessful();
});

it('allows editor to edit inactive record', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $editor->givePermissionTo(['Update:FinancialRecord', 'ViewAny:FinancialRecord', 'View:FinancialRecord']);

    $dept = Department::create(['name' => 'Test Dept']);

    $record = FinancialRecord::create([
        'user_id' => $editor->id,
        'department_id' => $dept->id,
        'record_date' => now(),
        'record_name' => 'Inactive Record',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
        'status' => false, // Inactive
    ]);

    Livewire::actingAs($editor)
        ->test(EditFinancialRecord::class, ['record' => $record->getRouteKey()])
        ->assertSuccessful();
});

it('prevents regular user from editing inactive record', function () {
    $dept = Department::create(['name' => 'Test Dept']);
    $user = User::factory()->create(['department_id' => $dept->id]);
    $user->assignRole('user');
    $user->givePermissionTo(['Update:FinancialRecord', 'ViewAny:FinancialRecord', 'View:FinancialRecord']);

    $record = FinancialRecord::create([
        'user_id' => $user->id,
        'department_id' => $dept->id,
        'record_date' => now(),
        'record_name' => 'Inactive Record',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
        'status' => false, // Inactive
    ]);

    Livewire::actingAs($user)
        ->test(EditFinancialRecord::class, ['record' => $record->getRouteKey()])
        ->assertForbidden();
});

it('allows regular user to edit active record', function () {
    $dept = Department::create(['name' => 'Test Dept']);
    $user = User::factory()->create(['department_id' => $dept->id]);
    $user->assignRole('user');
    $user->givePermissionTo(['Update:FinancialRecord', 'ViewAny:FinancialRecord', 'View:FinancialRecord']);

    $record = FinancialRecord::create([
        'user_id' => $user->id,
        'department_id' => $dept->id,
        'record_date' => now(),
        'record_name' => 'Active Record',
        'income_amount' => 1000,
        'income_percentage' => 100,
        'income_fixed' => 1000,
        'total_expense' => 500,
        'status' => true, // Active
    ]);

    Livewire::actingAs($user)
        ->test(EditFinancialRecord::class, ['record' => $record->getRouteKey()])
        ->assertSuccessful();
});
