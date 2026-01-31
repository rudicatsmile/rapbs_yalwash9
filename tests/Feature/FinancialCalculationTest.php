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
    $role = Role::firstOrCreate(['name' => 'super_admin']);
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
        // Formula: Amount - (Amount * (Percentage / 100))
        // 1,000,000 - (1,000,000 * 0.25) = 1,000,000 - 250,000 = 750,000
        ->assertSet('data.income_fixed', 750000)

        // Test change amount
        ->set('data.income_amount', 2000000)
        // 2,000,000 - (2,000,000 * 0.25) = 1,500,000
        ->assertSet('data.income_fixed', 1500000)

        // Test change percentage
        ->set('data.income_percentage', 10)
        // 2,000,000 - (2,000,000 * 0.10) = 1,800,000
        ->assertSet('data.income_fixed', 1800000)

        // Test edge case 0
        ->set('data.income_amount', 0)
        ->assertSet('data.income_fixed', 0)

        // Test edge case decimal
        ->set('data.income_amount', 1000000)
        ->set('data.income_percentage', 12.5)
        // 1,000,000 - 125,000 = 875,000
        ->assertSet('data.income_fixed', 875000)

        // Test 50%
        ->set('data.income_amount', 1000000)
        ->set('data.income_percentage', 50)
        ->assertSet('data.income_fixed', 500000)

        // Test 100%
        ->set('data.income_amount', 1000000)
        ->set('data.income_percentage', 100)
        ->assertSet('data.income_fixed', 0)

        // Test rounding
        ->set('data.income_amount', 1000)
        ->set('data.income_percentage', 33.33)
        // 1000 - 333.3 = 666.7
        ->assertSet('data.income_fixed', 666.7);
});
