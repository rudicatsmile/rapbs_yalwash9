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
use Filament\Actions\DeleteBulkAction;

class FinancialRecordBulkActionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_bulk_actions_are_hidden_for_regular_users()
    {
        // 1. Setup User Biasa
        $department = Department::create(['name' => 'IT']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');
        $user->givePermissionTo(['ViewAny:FinancialRecord']);

        // 2. Setup Record
        $record = FinancialRecord::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Test Record',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 900,
            'status' => true,
        ]);

        // 3. Assert Bulk Action Hidden
        // Kita tidak bisa langsung assert 'hidden' pada group secara mudah di Livewire test,
        // tapi kita bisa memastikan bahwa mencoba memanggil aksi tersebut akan gagal atau tidak tersedia.

        Livewire::actingAs($user)
            ->test(ListFinancialRecords::class)
            ->assertTableBulkActionHidden('duplicate');
    }

    public function test_bulk_delete_is_forbidden_for_regular_users()
    {
        $department = Department::create(['name' => 'IT']);
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole('user');
        $user->givePermissionTo(['ViewAny:FinancialRecord', 'Delete:FinancialRecord']); // Punya delete single, tapi tidak bulk

        $record = FinancialRecord::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Test Record',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 900,
            'status' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ListFinancialRecords::class)
            ->assertTableBulkActionHidden('delete');

        $this->assertDatabaseHas('financial_records', ['id' => $record->id]);
    }

    public function test_bulk_actions_are_allowed_for_admin()
    {
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord', 'Delete:FinancialRecord', 'Create:FinancialRecord']);

        $record = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Test Record',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 900,
            'status' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->callTableBulkAction('duplicate', [$record])
            ->assertHasNoErrors();

        $this->assertDatabaseCount('financial_records', 2);
    }
}
