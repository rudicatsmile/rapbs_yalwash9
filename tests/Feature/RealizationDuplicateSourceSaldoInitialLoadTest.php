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

class RealizationDuplicateSourceSaldoInitialLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Role::where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }
    }

    public function test_initial_load_calculates_cumulative_saldo_for_duplicate_source_rows(): void
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
            'amount' => 500000,
            'source_type' => 'Mandiri',
            'realisasi' => 0,
            'saldo' => 0,
        ]);

        RealizationExpenseLine::create([
            'financial_record_id' => $record->id,
            'expense_item_id' => $expenseItem->id,
            'description' => 'Baris 1',
            'allocated_amount' => 500000,
            'realisasi' => 100000,
        ]);

        RealizationExpenseLine::create([
            'financial_record_id' => $record->id,
            'expense_item_id' => $expenseItem->id,
            'description' => 'Baris 2',
            'allocated_amount' => 0,
            'realisasi' => 400000,
        ]);

        $component = Livewire::actingAs($user)
            ->test(RealizationResource\Pages\EditRealization::class, ['record' => $record->id]);

        $items = array_values($component->get('data.expenseItems'));

        $this->assertSame('500.000', $items[0]['amount'] ?? null);
        $this->assertSame('400.000', $items[1]['amount'] ?? null);
        $this->assertSame('400.000', $items[0]['saldo'] ?? null);
        $this->assertSame('0', $items[1]['saldo'] ?? null);
    }
}
