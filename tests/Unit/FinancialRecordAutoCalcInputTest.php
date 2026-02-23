<?php

namespace Tests\Unit;

use App\Filament\Resources\FinancialRecords\Schemas\FinancialRecordForm;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FinancialRecordAutoCalcInputTest extends TestCase
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
            return $wrapper->data[$key] ?? null;
        };

        $set = function ($key, $value) use ($wrapper) {
            $wrapper->data[$key] = $value;
        };

        return [$get, $set, $wrapper];
    }

    public function test_calculate_income_fixed_does_not_mutate_user_input_fields()
    {
        $data = [
            'income_amount' => '20.000',
            'income_percentage' => '5.000',
            'income_fixed' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        $this->callStaticMethod('calculateIncomeFixed', [$get, $set]);

        $this->assertEquals('20.000', $wrapper->data['income_amount']);
        $this->assertEquals('5.000', $wrapper->data['income_percentage']);
    }

    public function test_calculate_total_income_does_not_mutate_user_input_fields()
    {
        $data = [
            'income_fixed' => '15.000',
            'income_bos' => '5.000',
            'income_bos_other' => '2.000',
            'income_total' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        $this->callStaticMethod('calculateTotalIncome', [$get, $set]);

        $this->assertEquals('15.000', $wrapper->data['income_fixed']);
        $this->assertEquals('5.000', $wrapper->data['income_bos']);
        $this->assertEquals('2.000', $wrapper->data['income_bos_other']);
    }

    // Note: calculateTotalExpense uses typed Get/Set from Filament,
    // so we cover its behavior via existing feature tests instead of unit tests here.
}
