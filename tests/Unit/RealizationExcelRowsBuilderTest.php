<?php

namespace Tests\Unit;

use App\Filament\Exports\RealizationExcelRowsBuilder;
use App\Models\ExpenseItem;
use App\Models\FinancialRecord;
use App\Models\Realization;
use App\Models\RealizationExpenseLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealizationExcelRowsBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_includes_all_realization_lines_for_duplicate_source(): void
    {
        $user = User::factory()->create();

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'status' => true,
            'record_name' => 'Excel Test',
            'income_fixed' => 0,
            'income_bos' => 0,
            'income_total' => 0,
        ]);

        $realization = Realization::query()->findOrFail($record->id);

        $expenseItem = ExpenseItem::create([
            'financial_record_id' => $record->id,
            'description' => 'Sumber 1',
            'amount' => 1000000,
            'source_type' => 'Mandiri',
            'realisasi' => 300000,
            'saldo' => 700000,
        ]);

        RealizationExpenseLine::create([
            'financial_record_id' => $record->id,
            'expense_item_id' => $expenseItem->id,
            'description' => 'Belanja A',
            'allocated_amount' => 1000000,
            'realisasi' => 100000,
        ]);

        RealizationExpenseLine::create([
            'financial_record_id' => $record->id,
            'expense_item_id' => $expenseItem->id,
            'description' => 'Belanja B',
            'allocated_amount' => 0,
            'realisasi' => 200000,
        ]);

        $rows = RealizationExcelRowsBuilder::build($realization);
        $flat = implode("\n", array_map(fn ($r) => implode('|', array_map('strval', $r)), $rows));

        $this->assertStringContainsString('Pengeluaran', $flat);
        $this->assertStringContainsString('Sumber 1', $flat);
        $this->assertStringContainsString('Belanja A', $flat);
        $this->assertStringContainsString('Belanja B', $flat);
        $this->assertStringContainsString('Rp 100.000', $flat);
        $this->assertStringContainsString('Rp 200.000', $flat);
        $this->assertStringContainsString('Rp 300.000', $flat);
        $this->assertStringNotContainsString('Realisasi 1', $flat);
    }
}
