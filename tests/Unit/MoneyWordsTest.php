<?php

namespace Tests\Unit;

use App\Support\MoneyWords;
use PHPUnit\Framework\TestCase;

class MoneyWordsTest extends TestCase
{
    public function test_it_converts_dirhams_and_fils(): void
    {
        $this->assertSame(
            'Dirhams One Thousand Two Hundred Fifty-Six and Eighty-Five Fills',
            MoneyWords::aed(1256.85)
        );
    }

    public function test_it_uses_only_when_there_are_no_fils(): void
    {
        $this->assertSame('Dirhams One Hundred only', MoneyWords::aed(100));
    }
}
