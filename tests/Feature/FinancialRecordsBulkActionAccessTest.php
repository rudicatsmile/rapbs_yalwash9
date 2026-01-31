<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
use App\Models\Department;
use App\Models\FinancialRecord;
use App\Models\User;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialRecordsBulkActionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_bulk_actions_are_visible_to_admin()
    {
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');

        FinancialRecord::factory()->count(3)->create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->assertTableBulkActionVisible('duplicate')
            ->assertTableBulkActionVisible(DeleteBulkAction::class);
    }

    public function test_bulk_actions_are_visible_to_editor()
    {
        $department = Department::create(['name' => 'IT']);
        $editor = User::factory()->create(['department_id' => $department->id]);
        $editor->assignRole('editor');

        FinancialRecord::factory()->count(3)->create([
            'user_id' => $editor->id,
            'department_id' => $department->id,
        ]);

        Livewire::actingAs($editor)
            ->test(ListFinancialRecords::class)
            ->assertTableBulkActionVisible('duplicate')
            ->assertTableBulkActionVisible(DeleteBulkAction::class); // Editor usually can't delete, but request says "Bulk Action" restricted to admin/editor/super_admin. We'll check if editor has delete permission separately, but UI visibility is the key here.
    }

    public function test_bulk_actions_are_hidden_for_regular_user()
    {
        $department = Department::create(['name' => 'IT']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');

        FinancialRecord::factory()->count(3)->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
        ]);

        Livewire::actingAs($user)
            ->test(ListFinancialRecords::class)
            ->assertTableBulkActionHidden('duplicate')
            ->assertTableBulkActionHidden(DeleteBulkAction::class);
    }

    public function test_regular_user_cannot_execute_bulk_duplicate()
    {
        $department = Department::create(['name' => 'IT']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');

        $records = FinancialRecord::factory()->count(3)->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
        ]);

        Livewire::actingAs($user)
            ->test(ListFinancialRecords::class)
            ->callTableBulkAction('duplicate', $records)
            ->assertForbidden();
    }
}
