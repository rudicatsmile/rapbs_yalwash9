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

class RealizationDuplicateSourceSaldoValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }
    }

    public function test_duplicate_source_requires_cumulative_saldo_to_cover_previous_rows(): void
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
                        'amount' => '100',
                        'realisasi' => '100',
                    ],
                    [
                        'description' => 'Belanja B',
                        'expense_item_id' => (string) $expenseItem->id,
                        'amount' => '0',
                        'realisasi' => '50',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasErrors([
                'data.expenseItems.1.realisasi',
            ]);
    }
}
