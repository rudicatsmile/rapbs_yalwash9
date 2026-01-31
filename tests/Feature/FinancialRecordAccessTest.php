<?php

use App\Filament\Resources\FinancialRecords\FinancialRecordResource;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup Roles
    $this->roleUser = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->roleAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    
    // Setup Permissions (User needs view access)
    Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'View:FinancialRecord', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'Update:FinancialRecord', 'guard_name' => 'web']);
    
    $this->roleUser->givePermissionTo(['ViewAny:FinancialRecord', 'View:FinancialRecord', 'Update:FinancialRecord']);
    
    // Create Departments
    $this->deptA = Department::create(['name' => 'Dept A', 'urut' => 1]);
    $this->deptB = Department::create(['name' => 'Dept B', 'urut' => 2]);
    
    // Create Users
    $this->userA = User::factory()->create(['department_id' => $this->deptA->id]);
    $this->userA->assignRole('user');
    
    $this->userB = User::factory()->create(['department_id' => $this->deptB->id]);
    $this->userB->assignRole('user');
    
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    
    // Create Records
    $this->recordA = FinancialRecord::create([
        'user_id' => $this->userA->id,
        'department_id' => $this->deptA->id,
        'record_date' => now(),
        'record_name' => 'Record Dept A',
        'income_amount' => 1000000,
        'income_percentage' => 10,
        'income_fixed' => 100000,
        'total_expense' => 0,
    ]);
    
    $this->recordB = FinancialRecord::create([
        'user_id' => $this->userB->id,
        'department_id' => $this->deptB->id,
        'record_date' => now(),
        'record_name' => 'Record Dept B',
        'income_amount' => 2000000,
        'income_percentage' => 20,
        'income_fixed' => 400000,
        'total_expense' => 0,
    ]);
});

test('user can only see own department records in list', function () {
    $this->actingAs($this->userA)
        ->get(FinancialRecordResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee('Record Dept A')
        ->assertDontSee('Record Dept B');
        
    $this->actingAs($this->userB)
        ->get(FinancialRecordResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee('Record Dept B')
        ->assertDontSee('Record Dept A');
});

test('admin can see all department records', function () {
    $this->actingAs($this->admin)
        ->get(FinancialRecordResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee('Record Dept A')
        ->assertSee('Record Dept B');
});

test('user cannot edit other department record', function () {
    // Attempt to access Edit page of Record B as User A
    // Since getEloquentQuery filters it out, Filament should return 404
    $this->actingAs($this->userA)
        ->get(FinancialRecordResource::getUrl('edit', ['record' => $this->recordB]))
        ->assertNotFound();
});

test('user can edit own department record', function () {
    $this->actingAs($this->userA)
        ->get(FinancialRecordResource::getUrl('edit', ['record' => $this->recordA]))
        ->assertSuccessful();
});
