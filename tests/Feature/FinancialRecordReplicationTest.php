<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
use App\Models\Department;
use App\Models\ExpenseItem;
use App\Models\FinancialRecord;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialRecordReplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_single_replicate_action_copies_expense_items()
    {
        // Setup Admin
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');

        // Setup Record with Items
        $record = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Original Record',
            'income_amount' => 5000,
            'income_percentage' => 0,
            'income_fixed' => 5000,
            'total_expense' => 3000,
            'status' => true,
        ]);

        ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 1',
            'amount' => 1000,
        ]);

        ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 2',
            'amount' => 2000,
        ]);

        // Assert Initial State
        $this->assertDatabaseCount('financial_records', 1);
        $this->assertDatabaseCount('expense_items', 2);

        // Perform Replicate Action
        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->callTableAction('replicate', $record);

        // Assert Final State
        // Should be 2 records
        $this->assertDatabaseCount('financial_records', 2);
        // Should be 4 items (2 original + 2 copies)
        $this->assertDatabaseCount('expense_items', 4);

        $newRecord = FinancialRecord::where('id', '!=', $record->id)->first();
        $this->assertNotNull($newRecord);
        $this->assertEquals($record->record_name, $newRecord->record_name); 
        
        // Verify items for new record
        $this->assertEquals(2, $newRecord->expenseItems()->count());
        $this->assertEquals('Item 1', $newRecord->expenseItems()->first()->description);
    }

    public function test_bulk_duplicate_action_copies_expense_items()
    {
         // Setup Admin
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');

        // Setup Record with Items
        $record = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Original Record Bulk',
            'income_amount' => 5000,
            'income_percentage' => 0,
            'income_fixed' => 5000,
            'total_expense' => 1000,
            'status' => true,
        ]);

        ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Bulk Item 1',
            'amount' => 1000,
        ]);

         // Perform Bulk Action
        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->callTableBulkAction('duplicate', [$record]);

        // Assert Final State
        $this->assertDatabaseCount('financial_records', 2);
        $this->assertDatabaseCount('expense_items', 2); // 1 original + 1 copy

        $newRecord = FinancialRecord::where('id', '!=', $record->id)->first();
        $this->assertEquals(1, $newRecord->expenseItems()->count());
        $this->assertEquals('Bulk Item 1', $newRecord->expenseItems()->first()->description);
    }
}