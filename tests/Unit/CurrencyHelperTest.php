<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CurrencyHelperTest extends TestCase
{
    public function test_parse_indonesian_currency_stripped()
    {
        // Scenario: stripCharacters('.') has already run.

        $input = "10000"; // Was "10.000"
        $parsed = $this->parseMoney($input);
        $this->assertEquals(10000, $parsed);

        $input = "1000000"; // Was "1.000.000"
        $parsed = $this->parseMoney($input);
        $this->assertEquals(1000000, $parsed);

        $input = "10500,50"; // Was "10.500,50"
        $parsed = $this->parseMoney($input);
        $this->assertEquals(10500.50, $parsed);

        $input = "500";
        $parsed = $this->parseMoney($input);
        $this->assertEquals(500, $parsed);

        // Scenario: Already float (e.g. from DB or calculation)
        $input = 10000.50;
        $parsed = $this->parseMoney($input);
        $this->assertEquals(10000.50, $parsed);
    }

    protected function parseMoney($value)
    {
        if (empty($value)) {
            return 0;
        }

        if (is_numeric($value) && strpos((string) $value, ',') === false) {
            return (float) $value;
        }

        $value = str_replace(',', '.', (string) $value);
        return (float) $value;
    }
}
