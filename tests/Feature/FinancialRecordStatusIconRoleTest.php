<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialRecordStatusIconRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
        Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord']);
    }

    public function test_user_role_sees_success_when_status_zero_and_danger_when_status_one(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $user->givePermissionTo(['ViewAny:FinancialRecord']);

        $department = Department::create(['name' => 'IT']);
        $user->department_id = $department->id;
        $user->save();

        $recordInactive = FinancialRecord::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Record Inactive',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 100,
            'total_expense' => 50,
            'status' => 0,
        ]);

        $recordActive = FinancialRecord::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Record Active',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 100,
            'total_expense' => 50,
            'status' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(ListFinancialRecords::class)
            ->assertTableActionHasColor('status', 'success', $recordInactive)
            ->assertTableActionHasIcon('status', 'heroicon-m-check-circle', $recordInactive)
            ->assertTableActionHasColor('status', 'danger', $recordActive)
            ->assertTableActionHasIcon('status', 'heroicon-m-x-circle', $recordActive);
    }

    public function test_admin_role_sees_success_when_status_one_and_danger_when_status_zero(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord']);

        $department = Department::create(['name' => 'IT']);

        $recordInactive = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Record Inactive',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 100,
            'total_expense' => 50,
            'status' => 0,
        ]);

        $recordActive = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Record Active',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 100,
            'total_expense' => 50,
            'status' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->assertTableActionHasColor('status', 'danger', $recordInactive)
            ->assertTableActionHasIcon('status', 'heroicon-m-x-circle', $recordInactive)
            ->assertTableActionHasColor('status', 'success', $recordActive)
            ->assertTableActionHasIcon('status', 'heroicon-m-check-circle', $recordActive);
    }
}
