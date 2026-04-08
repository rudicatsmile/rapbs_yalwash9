<?php

namespace Tests\Feature;

use App\Filament\Resources\RealizationResource;
use App\Models\ExpenseItem;
use App\Models\FinancialRecord;
use App\Models\RealizationExpenseLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealizationDuplicateSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }
    }

    public function test_can_select_duplicate_source_in_same_form_and_persists_as_separate_lines(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'status' => true,
        ]);

        $expenseItem = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Sumber 1',
            'amount' => 1000,
            'source_type' => 'Mandiri',
            'realisasi' => 0,
            'saldo' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->fillForm([
                'expenseItems' => [
                    [
                        'description' => 'Belanja A',
                        'expense_item_id' => (string) $expenseItem->id,
                        'amount' => '1000',
                        'realisasi' => '100',
                    ],
                    [
                        'description' => 'Belanja B',
                        'expense_item_id' => (string) $expenseItem->id,
                        'amount' => '0',
                        'realisasi' => '200',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            2,
            RealizationExpenseLine::query()->where('financial_record_id', $record->id)->count()
        );

        $this->assertDatabaseHas('realization_expense_lines', [
            'financial_record_id' => $record->id,
            'expense_item_id' => $expenseItem->id,
            'description' => 'Belanja A',
            'allocated_amount' => 1000,
            'realisasi' => 100,
        ]);

        $this->assertDatabaseHas('realization_expense_lines', [
            'financial_record_id' => $record->id,
            'expense_item_id' => $expenseItem->id,
            'description' => 'Belanja B',
            'allocated_amount' => 0,
            'realisasi' => 200,
        ]);

        $this->assertDatabaseHas('expense_items', [
            'id' => $expenseItem->id,
            'financial_record_id' => $record->id,
            'allocated_amount' => 1000,
            'realisasi' => 300,
            'saldo' => 700,
            'is_selected_for_realization' => 1,
        ]);

        $this->assertDatabaseHas('financial_records', [
            'id' => $record->id,
            'total_expense' => 1000,
            'total_realization' => 300,
            'total_balance' => 700,
        ]);
    }
}
