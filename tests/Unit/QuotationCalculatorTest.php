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

    public function test_additional_discount_reduces_the_ex_vat_total_before_vat(): void
    {
        $totals = QuotationCalculator::totals([
            [
                'qty' => '1',
                'selling_price' => '1000',
                'discount_percent' => '0',
                'vat_percent' => '5',
            ],
        ]);

        $withDiscount = QuotationCalculator::applyAdditionalDiscount($totals, '100');

        $this->assertSame(1000.0, $withDiscount['subtotal']);
        $this->assertSame(100.0, $withDiscount['additional_discount']);
        $this->assertSame(900.0, $withDiscount['taxable_amount']);
        $this->assertSame(45.0, $withDiscount['vat_total']);
        $this->assertSame(945.0, $withDiscount['grand_total']);
    }

    public function test_it_converts_vat_inclusive_catalogue_prices_to_exclusive(): void
    {
        $this->assertSame(2110.48, QuotationCalculator::exclusiveFromInclusive(2215, 5));
        $this->assertSame(2215.0, QuotationCalculator::exclusiveFromInclusive(2215, 0));
        $this->assertTrue(QuotationCalculator::pricesMatch(2215, 2215.00));
        $this->assertFalse(QuotationCalculator::pricesMatch(2215, 2110.48));
    }
}
