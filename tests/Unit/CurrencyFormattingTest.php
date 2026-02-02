<?php

namespace Tests\Unit;

use App\Filament\Resources\FinancialRecords\Schemas\FinancialRecordForm;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CurrencyFormattingTest extends TestCase
{
    protected function parseMoney($value)
    {
        $method = new ReflectionMethod(FinancialRecordForm::class, 'parseMoney');
        $method->setAccessible(true);
        return $method->invoke(null, $value);
    }

    public function test_it_preserves_digits_for_large_integers()
    {
        // 4 digits
        $this->assertEquals(1000, $this->parseMoney('1.000'));
        
        // 7 digits (The specific user complaint case)
        $this->assertEquals(8000000, $this->parseMoney('8.000.000'));
        
        // 10 digits
        $this->assertEquals(1234567890, $this->parseMoney('1.234.567.890'));
        
        // 12 digits
        $this->assertEquals(100000000000, $this->parseMoney('100.000.000.000'));
    }

    public function test_it_handles_decimals_correctly_with_comma()
    {
        $this->assertEquals(10500.50, $this->parseMoney('10.500,50'));
        $this->assertEquals(0.5, $this->parseMoney('0,5'));
        $this->assertEquals(123.45, $this->parseMoney('123,45'));
    }

    public function test_it_handles_negative_numbers()
    {
        $this->assertEquals(-1000, $this->parseMoney('-1.000'));
        $this->assertEquals(-8000000, $this->parseMoney('-8.000.000'));
        $this->assertEquals(-10500.50, $this->parseMoney('-10.500,50'));
    }

    public function test_it_handles_raw_inputs_without_separators()
    {
        $this->assertEquals(8000000, $this->parseMoney('8000000'));
        $this->assertEquals(1000, $this->parseMoney('1000'));
    }

    public function test_it_handles_mixed_separators_robustly()
    {
        // Even if input has weird formatting, as long as dots are thousands and comma is decimal
        $this->assertEquals(1234567.89, $this->parseMoney('1.234.567,89'));
    }
}
