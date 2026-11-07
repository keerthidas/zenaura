<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Client;
use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\ReceiptVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_prioritises_this_months_confirmed_orders(): void
    {
        Carbon::setTestNow('2026-11-07 15:00:00');

        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'Harbour Fit-out',
            'trn' => '100111222333444',
        ]);

        $confirmed = $this->quotation($client, [
            'number' => 'ZN-2026-11-1',
            'base_number' => 'ZN-2026-11-1',
            'quote_date' => '2026-11-02',
            'status' => 'accepted',
            'salesperson' => 'Amina',
            'grand_total' => 2000,
        ]);

        $this->quotation($client, [
            'number' => 'ZN-2026-11-2',
            'base_number' => 'ZN-2026-11-2',
            'quote_date' => '2026-11-04',
            'status' => 'sent',
            'grand_total' => 1000,
        ]);

        $this->quotation($client, [
            'number' => 'ZN-2026-11-3',
            'base_number' => 'ZN-2026-11-3',
            'quote_date' => '2026-11-05',
            'status' => 'draft',
            'grand_total' => 500,
        ]);

        $this->quotation($client, [
            'number' => 'ZN-2026-10-1',
            'base_number' => 'ZN-2026-10-1',
            'quote_date' => '2026-10-18',
            'status' => 'accepted',
            'grand_total' => 1000,
        ]);

        $this->quotation($client, [
            'number' => 'ZN-2026-09-1',
            'base_number' => 'ZN-2026-09-1',
            'quote_date' => '2026-09-01',
            'status' => 'accepted',
            'grand_total' => 4000,
        ]);
        $this->quotation($client, [
            'number' => 'ZN-2026-09-1-1R',
            'base_number' => 'ZN-2026-09-1',
            'revision' => 1,
            'quote_date' => '2026-09-12',
            'status' => 'sent',
            'grand_total' => 4500,
        ]);

        $proforma = ProformaInvoice::create([
            'quotation_id' => $confirmed->id,
            'number' => 'ZS/PI/1126/1',
            'invoice_date' => '2026-11-03',
            'sequence_year' => 2026,
            'sequence_month' => 11,
            'sequence_number' => 1,
            'percent' => 50,
            'description' => 'Supply of sanitary fittings',
            'subtotal' => 285.71,
            'vat_total' => 14.29,
            'grand_total' => 300,
        ]);

        ReceiptVoucher::create([
            'proforma_invoice_id' => $proforma->id,
            'number' => 'ZS/RV/1126/1',
            'received_date' => '2026-11-06',
            'sequence_year' => 2026,
            'sequence_month' => 11,
            'sequence_number' => 1,
            'payment_mode' => 'Bank Transfer',
            'amount' => 100,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Confirmed orders this month')
            ->assertSee('Harbour Fit-out')
            ->assertSee('Amina')
            ->assertViewHas('current', function (array $current) {
                return $current['confirmed_count'] === 1
                    && $current['confirmed_amount'] === 2000.0
                    && $current['quotes_count'] === 2
                    && $current['quotes_amount'] === 3000.0
                    && $current['invoice_count'] === 1
                    && $current['invoiced'] === 300.0
                    && $current['receipt_count'] === 1
                    && $current['collected'] === 100.0;
            })
            ->assertViewHas('confirmedChange', ['direction' => 'up', 'text' => '+100%'])
            ->assertViewHas('conversion', 50)
            ->assertViewHas('draftCount', 1)
            ->assertViewHas('openQuoteCount', 2)
            ->assertViewHas('openQuoteValue', 5500.0)
            ->assertViewHas('ytdConfirmed', 3000.0)
            ->assertViewHas('outstanding', 200.0)
            ->assertDontSee('AED 4,000.00');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function quotation(Client $client, array $overrides): Quotation
    {
        return Quotation::create(array_merge([
            'client_id' => $client->id,
            'status' => 'draft',
            'subtotal' => 0,
            'discount_total' => 0,
            'vat_total' => 0,
            'grand_total' => 0,
        ], $overrides));
    }
}
