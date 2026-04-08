<?php

namespace Tests\Feature;

use App\Filament\Resources\RealizationResource;
use App\Models\ExpenseItem;
use App\Models\FinancialRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RealizationBalanceValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }
    }

    public function test_can_exceed_item_amount_as_long_as_global_balance_not_negative(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'status' => true,
        ]);

        $item1 = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 1',
            'amount' => 100,
            'source_type' => 'Mandiri',
            'realisasi' => 0,
            'saldo' => 0,
        ]);

        $item2 = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 2',
            'amount' => 300,
            'source_type' => 'Mandiri',
            'realisasi' => 0,
            'saldo' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->fillForm([
                'expenseItems' => [
                    [
                        'description' => 'Item 1',
                        'expense_item_id' => (string) $item1->id,
                        'realisasi' => '150',
                    ],
                    [
                        'description' => 'Item 2',
                        'expense_item_id' => (string) $item2->id,
                        'realisasi' => '200',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('financial_records', [
            'id' => $record->id,
            'total_realization' => 350,
            'total_balance' => 50,
        ]);
    }

    public function test_cannot_save_when_global_balance_negative(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'status' => true,
        ]);

        $item1 = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 1',
            'amount' => 100,
            'source_type' => 'Mandiri',
            'realisasi' => 0,
            'saldo' => 0,
        ]);

        $item2 = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Item 2',
            'amount' => 100,
            'source_type' => 'Mandiri',
            'realisasi' => 0,
            'saldo' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id])
            ->fillForm([
                'expenseItems' => [
                    [
                        'description' => 'Item 1',
                        'expense_item_id' => (string) $item1->id,
                        'realisasi' => '150',
                    ],
                    [
                        'description' => 'Item 2',
                        'expense_item_id' => (string) $item2->id,
                        'realisasi' => '100',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();
    }
}
