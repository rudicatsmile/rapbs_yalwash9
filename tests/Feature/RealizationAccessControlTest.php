<?php

namespace Tests\Feature;

use App\Filament\Resources\RealizationResource\Pages\ListRealizations;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealizationAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'ViewAny:FinancialRecord', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'Update:FinancialRecord', 'guard_name' => 'web']);
    }

    public function test_user_cannot_edit_locked_realization()
    {
        $department = \App\Models\Department::create(['name' => 'Test Dept']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');
        $user->givePermissionTo('ViewAny:FinancialRecord');
        $user->givePermissionTo('Update:FinancialRecord');

        $financialRecord = FinancialRecord::factory()->create([
            'status_realisasi' => true, // Locked
            'status' => true, // Active
            'department_id' => $department->id,
        ]);

        // Retrieve as Realization model to use RealizationPolicy
        $record = \App\Models\Realization::find($financialRecord->id);

        // Test Policy
        $this->assertTrue($user->can('Update:FinancialRecord'));
        $this->assertFalse($user->can('update', $record));

        // Test Filament Table Action
        Livewire::actingAs($user)
            ->test(ListRealizations::class)
            ->assertTableActionDisabled('edit', $record);
    }

    public function test_admin_can_edit_locked_realization()
    {
        $admin = User::factory()->create(); // Admin usually sees all
        $admin->assignRole('admin');
        $admin->givePermissionTo('ViewAny:FinancialRecord');
        $admin->givePermissionTo('Update:FinancialRecord');

        $financialRecord = FinancialRecord::factory()->create([
            'status_realisasi' => true, // Locked
            'status' => true, // Active
        ]);

        $record = \App\Models\Realization::find($financialRecord->id);

        // Test Policy
        $this->assertTrue($admin->can('update', $record));

        // Test Filament Table Action
        Livewire::actingAs($admin)
            ->test(ListRealizations::class)
            ->assertTableActionEnabled('edit', $record);
    }

    public function test_user_can_edit_unlocked_realization()
    {
        $department = \App\Models\Department::create(['name' => 'Test Dept']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');
        $user->givePermissionTo('ViewAny:FinancialRecord');
        $user->givePermissionTo('Update:FinancialRecord');

        $financialRecord = FinancialRecord::factory()->create([
            'status_realisasi' => false, // Unlocked
            'status' => true, // Active
            'department_id' => $department->id,
        ]);

        $record = \App\Models\Realization::find($financialRecord->id);

        // Test Policy
        $this->assertTrue($user->can('update', $record));

        // Test Filament Table Action
        Livewire::actingAs($user)
            ->test(ListRealizations::class)
            ->assertTableActionEnabled('edit', $record);
    }

    public function test_user_cannot_edit_inactive_record_regardless_of_realization_status()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $user->givePermissionTo('ViewAny:FinancialRecord');
        $user->givePermissionTo('Update:FinancialRecord');

        $financialRecord = FinancialRecord::factory()->create([
            'status_realisasi' => false,
            'status' => false, // Inactive
        ]);

        $record = \App\Models\Realization::find($financialRecord->id);

        $this->assertTrue($user->can('update', $record));
    }

    public function test_locked_rows_are_non_clickable_and_show_tooltip_for_user()
    {
        $department = \App\Models\Department::create(['name' => 'Test Dept']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');
        $user->givePermissionTo('ViewAny:FinancialRecord');
        $user->givePermissionTo('Update:FinancialRecord');

        $financialRecord = FinancialRecord::factory()->create([
            'status_realisasi' => true, // Locked
            'status' => true,
            'department_id' => $department->id,
        ]);
        $record = \App\Models\Realization::find($financialRecord->id);

        $this->actingAs($user);
        $response = $this->get(\App\Filament\Resources\RealizationResource::getUrl('index'));
        $response->assertStatus(200);
        $response->assertSee('Access Denied', false);
        $response->assertSee('pointer-events-none', false);
        $response->assertDontSee(\App\Filament\Resources\RealizationResource::getUrl('edit', ['record' => $record]));
    }

    public function test_backend_returns_403_early_for_locked_edit_by_user()
    {
        $department = \App\Models\Department::create(['name' => 'Test Dept']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');
        $user->givePermissionTo('ViewAny:FinancialRecord');
        $user->givePermissionTo('Update:FinancialRecord');

        $financialRecord = FinancialRecord::factory()->create([
            'status_realisasi' => true, // Locked
            'status' => true,
            'department_id' => $department->id,
        ]);
        $record = \App\Models\Realization::find($financialRecord->id);

        $this->actingAs($user);
        $response = $this->get(\App\Filament\Resources\RealizationResource::getUrl('edit', ['record' => $record]));
        $response->assertStatus(403);
    }
}
