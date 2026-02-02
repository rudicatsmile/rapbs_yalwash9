<?php

namespace Tests\Unit;

use App\Filament\Resources\FinancialRecords\Schemas\FinancialRecordForm;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FinancialRecordBosTest extends TestCase
{
    protected function callStaticMethod($name, $args = [])
    {
        $method = new ReflectionMethod(FinancialRecordForm::class, $name);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    protected function createMockGetSet($data)
    {
        // PHP array by reference trap in closures.
        // We need to return an object wrapper or use a specific structure.
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

    public function test_calculate_total_income_sums_fixed_and_bos()
    {
        // Setup initial data
        $data = [
            'income_fixed' => '1.000.000', // String formatted
            'income_bos' => '500.000',     // String formatted
            'total_income_display' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        // Call the calculation method
        $this->callStaticMethod('calculateTotalIncome', [$get, $set]);

        // Assert
        $this->assertEquals('1.500.000', $wrapper->data['total_income_display']);
    }

    public function test_calculate_total_income_handles_empty_or_zero()
    {
        $data = [
            'income_fixed' => '0',
            'income_bos' => '',
            'total_income_display' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        $this->callStaticMethod('calculateTotalIncome', [$get, $set]);

        $this->assertEquals('0', $wrapper->data['total_income_display']);
    }

    public function test_income_bos_formatting_and_storage()
    {
        // Simulate parseMoney (which we tested elsewhere, but ensuring it works in this context)
        $input = '2.500.000';
        $parsed = $this->callStaticMethod('parseMoney', [$input]);

        $this->assertEquals(2500000.0, $parsed);
    }
}
