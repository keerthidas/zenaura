<?php

namespace Tests\Unit;

use App\Services\QuotationCalculator;
use PHPUnit\Framework\TestCase;

class QuotationCalculatorTest extends TestCase
{
    public function test_it_parses_thousands_separators(): void
    {
        $this->assertSame(1114.29, QuotationCalculator::number('1,114.29'));
        $this->assertSame(1114.29, QuotationCalculator::number('1 114.29'));
    }

    public function test_line_totals_use_the_full_price_when_a_comma_is_present(): void
    {
        $line = QuotationCalculator::line([
            'qty' => '6',
            'selling_price' => '1,114.29',
            'discount_percent' => '0',
            'vat_percent' => '5',
        ]);

        $this->assertSame('6685.74', $line['amount']);
        $this->assertSame('334.29', $line['vat_amount']);
        $this->assertSame('7020.03', $line['line_total']);
    }
}
