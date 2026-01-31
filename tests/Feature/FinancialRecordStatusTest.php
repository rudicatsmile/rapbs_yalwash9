<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\FinancialRecordResource;
use App\Filament\Resources\FinancialRecords\Pages\CreateFinancialRecord;
use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
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
});

test('financial record has status field', function () {
    $user = User::factory()->create();
    $department = Department::create(['name' => 'IT']);

    $record = FinancialRecord::create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'record_date' => now(),
        'record_name' => 'Test Status',
        'income_amount' => 1000,
        'income_percentage' => 10,
        'income_fixed' => 100,
        'total_expense' => 50,
        'status' => true,
    ]);

    expect($record->status)->toBeTrue();
    expect($record->refresh()->status)->toBeTrue();

    $record->update(['status' => false]);
    expect($record->refresh()->status)->toBeFalse();
});

test('admin can see status toggle in form', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $department = Department::create(['name' => 'IT']);

    Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord']);
    Permission::firstOrCreate(['name' => 'Create:FinancialRecord']);
    $admin->givePermissionTo(['ViewAny:FinancialRecord', 'Create:FinancialRecord']);

    Livewire::actingAs($admin)
        ->test(CreateFinancialRecord::class)
        ->assertFormFieldExists('status')
        ->assertFormFieldIsVisible('status');
});

test('regular user cannot see status toggle in form', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $department = Department::create(['name' => 'IT']);
    $user->department_id = $department->id;
    $user->save();

    Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord']);
    Permission::firstOrCreate(['name' => 'Create:FinancialRecord']);
    $user->givePermissionTo(['ViewAny:FinancialRecord', 'Create:FinancialRecord']);

    Livewire::actingAs($user)
        ->test(CreateFinancialRecord::class)
        ->assertFormFieldIsHidden('status');
});

test('admin can toggle status via table action', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $department = Department::create(['name' => 'IT']);

    $record = FinancialRecord::create([
        'user_id' => $admin->id,
        'department_id' => $department->id,
        'record_date' => now(),
        'record_name' => 'Test Toggle',
        'income_amount' => 1000,
        'income_percentage' => 10,
        'income_fixed' => 100,
        'total_expense' => 50,
        'status' => true,
    ]);

    Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord']);
    $admin->givePermissionTo(['ViewAny:FinancialRecord']);

    Livewire::actingAs($admin)
        ->test(ListFinancialRecords::class)
        ->assertTableActionExists('status')
        ->callTableAction('status', $record);

    expect($record->refresh()->status)->toBeFalse();
});

test('regular user cannot toggle status via table action', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $department = Department::create(['name' => 'IT']);
    $user->department_id = $department->id;
    $user->save();

    $record = FinancialRecord::create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'record_date' => now(),
        'record_name' => 'Test Toggle',
        'income_amount' => 1000,
        'income_percentage' => 10,
        'income_fixed' => 100,
        'total_expense' => 50,
        'status' => true,
    ]);

    Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord']);
    $user->givePermissionTo(['ViewAny:FinancialRecord']);

    Livewire::actingAs($user)
        ->test(ListFinancialRecords::class)
        ->assertTableActionDisabled('status', $record);
});
