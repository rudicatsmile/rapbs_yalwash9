<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialRecords\Pages\ListFinancialRecords;
use App\Models\Department;
use App\Models\ExpenseItem;
use App\Models\FinancialRecord;
use App\Models\RealizationExpenseLine;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialRecordDuplicateStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_duplicate_copies_allocated_amount_zero(): void
    {
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord', 'Create:FinancialRecord']);

        $record = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Record Allocated 0',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 100,
            'income_bos' => 0,
            'income_bos_other' => 0,
            'income_total' => 100,
            'total_expense' => 50,
            'status' => true,
        ]);

        ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item A',
            'amount' => 50,
            'allocated_amount' => 0,
            'is_selected_for_realization' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->callTableAction('duplicate_record', $record);

        $this->assertDatabaseCount('financial_records', 2);

        $duplicate = FinancialRecord::latest('id')->first();
        $duplicate->load('expenseItems');

        $this->assertCount(1, $duplicate->expenseItems);
        $this->assertSame('0.00', (string) $duplicate->expenseItems->first()->allocated_amount);
    }

    public function test_duplicate_copies_is_selected_for_realization_true(): void
    {
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord', 'Create:FinancialRecord']);

        $record = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Record Selected True',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 100,
            'income_bos' => 0,
            'income_bos_other' => 0,
            'income_total' => 100,
            'total_expense' => 50,
            'status' => true,
        ]);

        ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item A',
            'amount' => 50,
            'allocated_amount' => 50,
            'is_selected_for_realization' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->callTableAction('duplicate_record', $record);

        $duplicate = FinancialRecord::latest('id')->first();
        $duplicate->load('expenseItems');

        $this->assertTrue((bool) $duplicate->expenseItems->first()->is_selected_for_realization);
    }

    public function test_duplicate_copies_realization_expense_lines_and_maps_foreign_keys(): void
    {
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord', 'Create:FinancialRecord']);

        $record = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Record With Lines',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 100,
            'income_bos' => 0,
            'income_bos_other' => 0,
            'income_total' => 100,
            'total_expense' => 50,
            'status' => true,
        ]);

        $item = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item A',
            'amount' => 50,
            'allocated_amount' => 50,
            'is_selected_for_realization' => true,
        ]);

        RealizationExpenseLine::create([
            'financial_record_id' => $record->id,
            'expense_item_id' => $item->id,
            'description' => 'Line 1',
            'allocated_amount' => 50,
            'realisasi' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->callTableAction('duplicate_record', $record);

        $duplicate = FinancialRecord::latest('id')->first();
        $duplicate->load('expenseItems', 'realizationExpenseLines');

        $this->assertCount(1, $duplicate->expenseItems);
        $this->assertCount(1, $duplicate->realizationExpenseLines);

        $newItemId = $duplicate->expenseItems->first()->id;
        $line = $duplicate->realizationExpenseLines->first();

        $this->assertSame($duplicate->id, $line->financial_record_id);
        $this->assertSame($newItemId, $line->expense_item_id);
        $this->assertSame('Line 1', $line->description);
        $this->assertSame('10.00', (string) $line->realisasi);
    }

    public function test_duplicate_handles_constraint_violation_like_inconsistent_reference(): void
    {
        $department = Department::create(['name' => 'IT']);
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin');
        $admin->givePermissionTo(['ViewAny:FinancialRecord', 'Create:FinancialRecord']);

        $record = FinancialRecord::create([
            'user_id' => $admin->id,
            'department_id' => $department->id,
            'record_date' => now(),
            'record_name' => 'Record Inconsistent',
            'income_amount' => 1000,
            'income_percentage' => 10,
            'income_fixed' => 100,
            'income_bos' => 0,
            'income_bos_other' => 0,
            'income_total' => 100,
            'total_expense' => 50,
            'status' => true,
        ]);

        ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item A',
            'amount' => 50,
            'allocated_amount' => 50,
            'is_selected_for_realization' => true,
        ]);

        RealizationExpenseLine::create([
            'financial_record_id' => $record->id,
            'expense_item_id' => 999999,
            'description' => 'Bad Line',
            'allocated_amount' => 0,
            'realisasi' => 0,
        ]);

        Livewire::actingAs($admin)
            ->test(ListFinancialRecords::class)
            ->callTableAction('duplicate_record', $record);

        $this->assertDatabaseCount('financial_records', 1);
        $this->assertDatabaseCount('expense_items', 1);
        $this->assertDatabaseCount('realization_expense_lines', 1);
    }
}

