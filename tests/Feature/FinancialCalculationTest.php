<?php

use App\Filament\Resources\FinancialRecords\FinancialRecordResource;
use App\Filament\Resources\FinancialRecords\Pages\CreateFinancialRecord;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->department = Department::create([
        'name' => 'IT',
        'urut' => 1,
        'description' => 'IT Department',
    ]);

    $this->user = User::factory()->create([
        'department_id' => $this->department->id,
    ]);

    // Setup permissions
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->user->assignRole($role);
});

it('calculates income fixed correctly', function () {
    Livewire::actingAs($this->user)
        ->test(CreateFinancialRecord::class)
        ->set('data.department_id', $this->department->id)
        ->set('data.record_date', now())
        ->set('data.record_name', 'Test Record')
        ->set('data.income_amount', 1000000)
        ->set('data.income_percentage', 25)
        ->assertSet('data.income_fixed', '999.975')

        // Test change amount
        ->set('data.income_amount', 2000000)
        ->assertSet('data.income_fixed', '1.999.975')

        // Test change percentage
        ->set('data.income_percentage', 10)
        ->assertSet('data.income_fixed', '1.999.990')

        // Test edge case 0
        ->set('data.income_amount', 0)
        ->assertSet('data.income_fixed', '0');
});
