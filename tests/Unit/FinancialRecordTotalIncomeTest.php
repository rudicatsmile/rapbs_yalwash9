<?php

namespace Tests\Unit;

use App\Filament\Resources\FinancialRecords\Schemas\FinancialRecordForm;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FinancialRecordTotalIncomeTest extends TestCase
{
    protected function callStaticMethod($name, $args = [])
    {
        $method = new ReflectionMethod(FinancialRecordForm::class, $name);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    protected function createMockGetSet($data)
    {
        $wrapper = new \stdClass();
        $wrapper->data = $data;

        $get = function ($key) use ($wrapper) {
            return $wrapper->data[$key] ?? 0;
        };

        $set = function ($key, $value) use ($wrapper) {
            $wrapper->data[$key] = $value;
        };

        return [$get, $set, $wrapper];
    }

    public function test_calculate_total_income_sums_fixed_and_bos_to_income_total()
    {
        // Setup initial data
        $data = [
            'income_fixed' => '1.000.000', // String formatted
            'income_bos' => '500.000',     // String formatted
            'income_total' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        // Call the calculation method
        $this->callStaticMethod('calculateTotalIncome', [$get, $set]);

        // Assert that income_total is updated, not total_income_display
        $this->assertEquals('1.500.000', $wrapper->data['income_total']);
    }

    public function test_calculate_total_income_handles_null_or_empty_values()
    {
        $data = [
            'income_fixed' => null,
            'income_bos' => '',
            'income_total' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        $this->callStaticMethod('calculateTotalIncome', [$get, $set]);

        $this->assertEquals('0', $wrapper->data['income_total']);
    }

    public function test_calculate_total_income_handles_zero_values()
    {
        $data = [
            'income_fixed' => '0',
            'income_bos' => '0',
            'income_total' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        $this->callStaticMethod('calculateTotalIncome', [$get, $set]);

        $this->assertEquals('0', $wrapper->data['income_total']);
    }

    public function test_income_total_formatting_and_parsing()
    {
        // Test parsing function which is used in dehydrateStateUsing
        $input = '3.750.000';
        $parsed = $this->callStaticMethod('parseMoney', [$input]);

        $this->assertEquals(3750000.0, $parsed);
    }
}
