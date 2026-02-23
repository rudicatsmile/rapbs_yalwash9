<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ThousandSeparatorFormattingTest extends TestCase
{
    protected function formatThousands(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        if ($digits === '') {
            return '';
        }

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $digits);
    }

    public function test_formats_simple_numbers_with_thousand_separators()
    {
        $this->assertSame('1.000', $this->formatThousands('1000'));
        $this->assertSame('20.000', $this->formatThousands('20000'));
        $this->assertSame('1.234.567', $this->formatThousands('1234567'));
    }

    public function test_preserves_trailing_zeros()
    {
        $this->assertSame('20.000', $this->formatThousands('20000'));
        $this->assertSame('200.000', $this->formatThousands('200000'));
        $this->assertSame('2.000.000', $this->formatThousands('2000000'));
    }

    public function test_ignores_non_digit_characters()
    {
        $this->assertSame('20.000', $this->formatThousands('20.000'));
        $this->assertSame('20.000', $this->formatThousands('20,000'));
        $this->assertSame('20.000', $this->formatThousands('Rp 20.000'));
    }

    public function test_handles_empty_and_zero_values()
    {
        $this->assertSame('', $this->formatThousands(''));
        $this->assertSame('0', $this->formatThousands('0'));
        $this->assertSame('0', $this->formatThousands('000'));
    }
}

