<?php

namespace Tests\Feature;

use App\Filament\Resources\RealizationResource\Tables\RealizationTable;
use App\Models\ExpenseItem;
use App\Models\FinancialRecord;
use App\Models\RealizationExpenseLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealizationPdfDuplicateSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_view_includes_all_duplicate_realization_lines(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = FinancialRecord::factory()->create([
            'user_id' => $user->id,
            'status' => true,
            'record_name' => 'PDF Test',
        ]);

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

        $record->refresh();
        $record->load('realizationExpenseLines', 'expenseItems', 'department');

        $html = view('pdf.financial_record', ['record' => $record])->render();

        $this->assertStringContainsString('Sumber 1', $html);
        $this->assertStringContainsString('Belanja A', $html);
        $this->assertStringContainsString('Belanja B', $html);
        $this->assertStringContainsString('100.000', $html);
        $this->assertStringContainsString('200.000', $html);
        $this->assertStringContainsString('1.000.000', $html);
    }
}

