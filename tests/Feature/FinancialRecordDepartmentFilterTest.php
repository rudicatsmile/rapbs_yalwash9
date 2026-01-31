<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialRecordDepartmentFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_department_filter_is_visible_for_admin()
    {
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo('ViewAny:FinancialRecord');

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->assertTableFilterVisible('department_id');
    }

    public function test_department_filter_is_hidden_for_regular_user()
    {
        $department = Department::create(['name' => 'IT']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');
        $user->givePermissionTo('ViewAny:FinancialRecord');

        Livewire::actingAs($user)
            ->test(ListFinancialRecords::class)
            ->assertTableFilterHidden('department_id');
    }

    public function test_department_filter_works_for_admin()
    {
        $dept1 = Department::create(['name' => 'Dept A']);
        $dept2 = Department::create(['name' => 'Dept B']);
        
        $admin = User::factory()->create(['department_id' => $dept1->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo('ViewAny:FinancialRecord');

        $record1 = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $dept1->id,
            'record_date' => now(),
            'record_name' => 'Record A',
            'income_amount' => 1000,
            'status' => true,
        ]);

        $record2 = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $dept2->id,
            'record_date' => now(),
            'record_name' => 'Record B',
            'income_amount' => 2000,
            'status' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->assertCanSeeTableRecords([$record1, $record2])
            ->filterTable('department_id', $dept1->id)
            ->assertCanSeeTableRecords([$record1])
            ->assertCanNotSeeTableRecords([$record2]);
    }
}