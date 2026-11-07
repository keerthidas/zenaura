<?php

namespace Tests\Feature;

use App\Livewire\Reports\Sales;
use App\Models\Client;
use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\ReceiptVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('reports.sales'))->assertRedirect(route('login'));
    }

    public function test_sales_report_summarises_a_year_by_month(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::create([
            'name' => 'Harbour Fit-out',
            'trn' => '100111222333444',
        ]);

        $accepted = $this->quotation($client, [
            'number' => 'ZN-2026-01-1',
            'base_number' => 'ZN-2026-01-1',
            'quote_date' => '2026-01-15',
            'status' => 'accepted',
            'salesperson' => 'Amina',
            'grand_total' => 1050,
        ]);

        $this->quotation($client, [
            'number' => 'ZN-2026-03-1',
            'base_number' => 'ZN-2026-03-1',
            'quote_date' => '2026-03-02',
            'status' => 'draft',
            'grand_total' => 420,
        ]);

        $this->quotation($client, [
            'number' => 'ZN-2025-12-1',
            'base_number' => 'ZN-2025-12-1',
            'quote_date' => '2025-12-20',
            'status' => 'accepted',
            'grand_total' => 9000,
        ]);

        $removed = $this->quotation($client, [
            'number' => 'ZN-2026-01-9',
            'base_number' => 'ZN-2026-01-9',
            'quote_date' => '2026-01-10',
            'status' => 'accepted',
            'grand_total' => 8000,
        ]);
        $removed->delete();

        $replaced = $this->quotation($client, [
            'number' => 'ZN-2026-04-1',
            'base_number' => 'ZN-2026-04-1',
            'quote_date' => '2026-04-01',
            'status' => 'accepted',
            'grand_total' => 5000,
        ]);
        $this->quotation($client, [
            'number' => 'ZN-2026-04-1-1R',
            'base_number' => 'ZN-2026-04-1',
            'revision' => 1,
            'quote_date' => '2026-04-12',
            'status' => 'accepted',
            'grand_total' => 1200,
        ]);
        $revisedProforma = ProformaInvoice::create([
            'quotation_id' => $replaced->id,
            'number' => 'ZS/PI/0126/9',
            'invoice_date' => '2026-01-22',
            'sequence_year' => 2026,
            'sequence_month' => 1,
            'sequence_number' => 9,
            'percent' => 100,
            'description' => 'Replaced quotation',
            'subtotal' => 740,
            'vat_total' => 37,
            'grand_total' => 777,
        ]);
        ReceiptVoucher::create([
            'proforma_invoice_id' => $revisedProforma->id,
            'number' => 'ZS/RV/0126/9',
            'received_date' => '2026-01-25',
            'sequence_year' => 2026,
            'sequence_month' => 1,
            'sequence_number' => 9,
            'payment_mode' => 'Cash',
            'amount' => 333,
        ]);

        $proforma = ProformaInvoice::create([
            'quotation_id' => $accepted->id,
            'number' => 'ZS/PI/0126/1',
            'invoice_date' => '2026-01-20',
            'sequence_year' => 2026,
            'sequence_month' => 1,
            'sequence_number' => 1,
            'percent' => 50,
            'description' => 'Supply of sanitary fittings',
            'subtotal' => 500,
            'vat_total' => 25,
            'grand_total' => 525,
        ]);

        ReceiptVoucher::create([
            'proforma_invoice_id' => $proforma->id,
            'number' => 'ZS/RV/0226/1',
            'received_date' => '2026-02-05',
            'sequence_year' => 2026,
            'sequence_month' => 2,
            'sequence_number' => 1,
            'payment_mode' => 'Bank Transfer',
            'amount' => 200,
        ]);

        $this->actingAs($user)
            ->get(route('reports.sales', ['year' => 2026]))
            ->assertOk()
            ->assertSee('Sales report')
            ->assertSee('January')
            ->assertSee('Harbour Fit-out')
            ->assertSee('Amina')
            ->assertSee('AED 1,050.00')
            ->assertSee('AED 1,200.00')
            ->assertSee('AED 525.00')
            ->assertSee('AED 9,000.00')
            ->assertDontSee('AED 8,000.00')
            ->assertDontSee('AED 5,000.00');

        Livewire::actingAs($user)
            ->test(Sales::class, ['year' => 2026])
            ->assertViewHas('summary', function (array $summary) {
                return $summary['quotations'] === 3
                    && $summary['quoted'] === 2670.0
                    && $summary['sales'] === 2
                    && $summary['sales_amount'] === 2250.0
                    && $summary['invoiced'] === 525.0
                    && $summary['collected'] === 200.0
                    && $summary['outstanding'] === 325.0
                    && $summary['previous_sales'] === 9000.0;
            })
            ->assertViewHas('months', function (array $months) {
                $january = collect($months)->firstWhere('label', 'January');
                $february = collect($months)->firstWhere('label', 'February');
                $april = collect($months)->firstWhere('label', 'April');

                return $january['sales'] === 1
                    && $january['sales_amount'] === 1050.0
                    && $january['invoiced'] === 525.0
                    && $february['collected'] === 200.0
                    && $february['sales'] === 0
                    && $april['sales'] === 1
                    && $april['sales_amount'] === 1200.0;
            })
            ->set('year', 2025)
            ->assertViewHas('summary', function (array $summary) {
                return $summary['sales'] === 1
                    && $summary['sales_amount'] === 9000.0
                    && $summary['invoiced'] === 0.0;
            });
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
