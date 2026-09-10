<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Quotation;
use Illuminate\Support\Carbon;

class QuotationNumberService
{
    public function preview(?Carbon $date = null): string
    {
        [$year, $month, $seq] = $this->nextSequence($date ?? now());

        return $this->format($year, $month, $seq);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public function nextSequence(?Carbon $date = null): array
    {
        $date ??= now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('m');

        $max = Quotation::query()
            ->where('sequence_year', $year)
            ->where('sequence_month', $month)
            ->max('sequence_number');

        return [$year, $month, (int) $max + 1];
    }

    public function format(int $year, int $month, int $seq, ?int $revision = null): string
    {
        $prefix = $this->prefix();
        $base = sprintf('%s-%04d-%02d-%04d', $prefix, $year, $month, $seq);

        if ($revision) {
            return $base.'-'.$revision.'R';
        }

        return $base;
    }

    /**
     * @return array{year: int, month: int, sequence: int, revision: ?int}|null
     */
    public function parse(string $number): ?array
    {
        $prefix = preg_quote($this->prefix(), '/');

        if (! preg_match('/^'.$prefix.'-(\d{4})-(\d{2})-(\d{4})(?:-(\d+)R)?$/i', trim($number), $m)) {
            return null;
        }

        return [
            'year' => (int) $m[1],
            'month' => (int) $m[2],
            'sequence' => (int) $m[3],
            'revision' => isset($m[4]) && $m[4] !== '' ? (int) $m[4] : null,
        ];
    }

    /**
     * @return array{number: string, base_number: string, revision: int, sequence_year: ?int, sequence_month: ?int, sequence_number: ?int}
     */
    public function revisionOf(Quotation $quotation): array
    {
        $max = Quotation::query()
            ->where('base_number', $quotation->base_number)
            ->max('revision');

        $revision = (int) $max + 1;

        return [
            'number' => $quotation->base_number.'-'.$revision.'R',
            'base_number' => $quotation->base_number,
            'revision' => $revision,
            'sequence_year' => $quotation->sequence_year,
            'sequence_month' => $quotation->sequence_month,
            'sequence_number' => $quotation->sequence_number,
        ];
    }

    /**
     * @return array{number: string, base_number: string, revision: ?int, sequence_year: ?int, sequence_month: ?int, sequence_number: ?int}
     */
    public function resolveForSave(string $number, ?Carbon $date = null): array
    {
        $parsed = $this->parse($number);

        if ($parsed) {
            $base = $this->format($parsed['year'], $parsed['month'], $parsed['sequence']);

            return [
                'number' => $this->format($parsed['year'], $parsed['month'], $parsed['sequence'], $parsed['revision']),
                'base_number' => $base,
                'revision' => $parsed['revision'],
                'sequence_year' => $parsed['year'],
                'sequence_month' => $parsed['month'],
                'sequence_number' => $parsed['sequence'],
            ];
        }

        [$year, $month, $seq] = $this->nextSequence($date ?? now());

        return [
            'number' => $number,
            'base_number' => $number,
            'revision' => null,
            'sequence_year' => $year,
            'sequence_month' => $month,
            'sequence_number' => $seq,
        ];
    }

    public function prefix(): string
    {
        return CompanySetting::current()->quote_prefix ?: 'ZN';
    }
}
