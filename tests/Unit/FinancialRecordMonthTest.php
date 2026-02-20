<?php

namespace Tests\Unit;

use App\Models\FinancialRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialRecordMonthTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_is_fillable_and_persisted()
    {
        $record = FinancialRecord::factory()->create([
            'month' => 5,
        ]);

        $this->assertEquals(5, $record->month);
        $this->assertDatabaseHas('financial_records', [
            'id' => $record->id,
            'month' => 5,
        ]);
    }

    public function test_month_accessor_returns_correct_name()
    {
        $record = FinancialRecord::factory()->create([
            'month' => 3,
        ]);

        $this->assertEquals('Maret', $record->month_name);
    }

    public function test_invalid_month_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);

        FinancialRecord::factory()->create([
            'month' => 13,
        ]);
    }

    public function test_scope_for_month_filters_correctly()
    {
        FinancialRecord::factory()->create(['month' => 1]);
        FinancialRecord::factory()->create(['month' => 2]);

        $januaryCount = FinancialRecord::forMonth(1)->count();

        $this->assertEquals(1, $januaryCount);
    }
}

