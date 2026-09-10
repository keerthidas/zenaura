<?php

namespace App\Support;

class MoneyWords
{
    public static function aed(float $amount): string
    {
        $dirhams = (int) floor($amount + 0.00001);
        $fils = (int) round(($amount - $dirhams) * 100);

        if ($fils === 100) {
            $dirhams++;
            $fils = 0;
        }

        $text = 'Dirhams '.self::integer($dirhams);

        if ($fils > 0) {
            return $text.' and '.self::integer($fils).' Fills';
        }

        return $text.' only';
    }

    public static function integer(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        if ($number < 0) {
            return 'Minus '.self::integer(abs($number));
        }

        $words = [];

        $billions = intdiv($number, 1_000_000_000);
        if ($billions) {
            $words[] = self::belowThousand($billions).' Billion';
            $number %= 1_000_000_000;
        }

        $millions = intdiv($number, 1_000_000);
        if ($millions) {
            $words[] = self::belowThousand($millions).' Million';
            $number %= 1_000_000;
        }

        $thousands = intdiv($number, 1000);
        if ($thousands) {
            $words[] = self::belowThousand($thousands).' Thousand';
            $number %= 1000;
        }

        if ($number) {
            $words[] = self::belowThousand($number);
        }

        return implode(' ', $words);
    }

    private static function belowThousand(int $number): string
    {
        $small = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        ];
        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
        ];

        $parts = [];
        $hundreds = intdiv($number, 100);

        if ($hundreds) {
            $parts[] = $small[$hundreds].' Hundred';
            $number %= 100;
        }

        if ($number === 0) {
            return implode(' ', $parts);
        }

        if ($number < 20) {
            $parts[] = $small[$number];
        } else {
            $ten = $tens[intdiv($number, 10)];
            $unit = $number % 10;
            $parts[] = $unit ? $ten.'-'.$small[$unit] : $ten;
        }

        return implode(' ', $parts);
    }
}
