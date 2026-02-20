<?php

namespace Tests\Unit;

use App\Filament\Resources\FinancialRecords\Schemas\FinancialRecordForm;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FinancialRecordIncomeFixedTest extends TestCase
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

    public function test_income_fixed_is_income_minus_risk_amount()
    {
        $data = [
            'income_amount' => '10.000',
            'income_percentage' => '2.000',
            'income_fixed' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        $this->callStaticMethod('calculateIncomeFixed', [$get, $set]);

        $this->assertEquals('8.000', $wrapper->data['income_fixed']);
    }

    public function test_income_fixed_does_not_go_below_zero()
    {
        $data = [
            'income_amount' => '5.000',
            'income_percentage' => '10.000',
            'income_fixed' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        $this->callStaticMethod('calculateIncomeFixed', [$get, $set]);

        $this->assertEquals('0', $wrapper->data['income_fixed']);
    }

    public function test_income_fixed_handles_empty_values()
    {
        $data = [
            'income_amount' => '',
            'income_percentage' => '',
            'income_fixed' => 0,
        ];

        [$get, $set, $wrapper] = $this->createMockGetSet($data);

        $this->callStaticMethod('calculateIncomeFixed', [$get, $set]);

        $this->assertEquals('0', $wrapper->data['income_fixed']);
    }
}

