<?php

use App\Filament\Resources\FinancialRecords\FinancialRecordResource;
use App\Filament\Resources\FinancialRecords\Pages\CreateFinancialRecord;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('can create financial record', function () {
    $user = User::factory()->create();
    $department = Department::create(['name' => 'IT']);

    $record = FinancialRecord::create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'record_date' => now(),
        'record_name' => 'Test Budget',
        'income_amount' => 1000000,
        'income_percentage' => 10,
        'income_fixed' => 100000,
        'total_expense' => 50000,
    ]);

    expect($record)->toBeInstanceOf(FinancialRecord::class);
    expect($record->income_fixed)->toEqual(100000);
    expect($record->department_id)->toBe($department->id);
});

test('financial record has expense items', function () {
    $user = User::factory()->create();
    $department = Department::create(['name' => 'IT']);

    $record = FinancialRecord::create([
        'user_id' => $user->id,
        'department_id' => $department->id,
        'record_date' => now(),
        'record_name' => 'Test Budget',
        'income_amount' => 1000000,
        'income_percentage' => 10,
        'income_fixed' => 100000,
        'total_expense' => 0,
    ]);

    $record->expenseItems()->create([
        'description' => 'Coffee',
        'amount' => 25000,
    ]);

    expect($record->expenseItems)->toHaveCount(1);
    expect($record->expenseItems->first()->amount)->toEqual(25000);
});

test('can access financial records menu', function () {
    $user = User::factory()->create();

    // Ensure permission exists
    Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord']);
    $user->givePermissionTo('ViewAny:FinancialRecord');

    $this->actingAs($user)
        ->get(FinancialRecordResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee('RAPB Sekolah');
});

test('can access create financial record page', function () {
    $user = User::factory()->create();

    // Ensure permissions exist
    Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord']);
    Permission::firstOrCreate(['name' => 'Create:FinancialRecord']);
    $user->givePermissionTo(['ViewAny:FinancialRecord', 'Create:FinancialRecord']);

    $this->actingAs($user)
        ->get(FinancialRecordResource::getUrl('create'))
        ->assertSuccessful();
});

test('can create financial record via filament form', function () {
    $user = User::factory()->create();
    $department = Department::create(['name' => 'IT']);

    Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord']);
    Permission::firstOrCreate(['name' => 'Create:FinancialRecord']);
    $user->givePermissionTo(['ViewAny:FinancialRecord', 'Create:FinancialRecord']);

    Livewire::actingAs($user)
        ->test(CreateFinancialRecord::class)
        ->fillForm([
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Filament Created',
            'income_amount' => 1000000,
            'income_percentage' => 10,
            'income_fixed' => 100000,
            'total_expense' => 0,
            'expenseItems' => [],
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('financial_records', [
        'user_id' => $user->id,
        'department_id' => $department->id,
        'record_name' => 'Filament Created',
    ]);
});
