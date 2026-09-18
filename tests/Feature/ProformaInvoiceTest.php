<?php

namespace Tests\Feature;

use App\Livewire\Quotations\Index;
use App\Livewire\Quotations\QuotationEditor;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\ProformaInvoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProformaInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_generate_percentage_proformas_up_to_one_hundred(): void
    {
        $quote = $this->makeQuotation();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openProforma', $quote->id)
            ->assertSet('proformaQuoteId', $quote->id)
            ->assertSet('proformaDescription', ProformaInvoice::DEFAULT_DESCRIPTION)
            ->assertSet('proformaPaymentTerms', 'CASH IN ADVANCE')
            ->assertSet('proformaDeliveryTerms', 'EX STOCK')
            ->assertSee('Proforma invoice')
            ->set('proformaPercent', '50')
            ->set('proformaPaymentTerms', '50% Advance, 50% Before Delivery')
            ->set('proformaDeliveryTerms', 'Within 2 days from Payment')
            ->call('createProforma')
            ->assertHasNoErrors();

        $first = ProformaInvoice::first();
        $this->assertNotNull($first);
        $this->assertSame(50.0, (float) $first->percent);
        $this->assertStringStartsWith('ZS/PI/'.now()->format('my').'/', $first->number);
        $this->assertSame('50% Advance, 50% Before Delivery', $first->payment_terms);
        $this->assertSame('Within 2 days from Payment', $first->delivery_terms);
        $this->assertEquals(130.50, (float) $first->subtotal);
        $this->assertEquals(137.03, (float) $first->grand_total);
        $this->assertSame(50.0, $quote->fresh()->load('proformas')->proformaRemainingPercent());

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openProforma', $quote->id)
            ->set('proformaPercent', '60')
            ->call('createProforma')
            ->assertHasErrors(['proformaPercent']);

        $this->assertSame(1, ProformaInvoice::count());

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openProforma', $quote->id)
            ->set('proformaPercent', '50')
            ->set('proformaDescription', 'Balance sanitary fittings supply')
            ->call('createProforma')
            ->assertHasNoErrors();

        $this->assertSame(2, ProformaInvoice::count());
        $this->assertSame(0.0, $quote->fresh()->load('proformas')->proformaRemainingPercent());
        $this->assertEquals(274.05, (float) ProformaInvoice::sum('grand_total'));

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('openProforma', $quote->id)
            ->assertSee('fully covered')
            ->set('proformaPercent', '1')
            ->call('createProforma')
            ->assertHasErrors(['proformaPercent']);

        $this->actingAs($user)
            ->get(route('quotations.proforma', [$quote, $first]))
            ->assertOk();

        $html = view('pdf.proforma', [
            'quotation' => $quote->fresh()->load(['client', 'proformas']),
            'proforma' => $first,
            'settings' => CompanySetting::current(),
            'logoDataUri' => null,
            'signatureDataUri' => null,
            'stampDataUri' => null,
        ])->render();

        $this->assertStringContainsString('Proforma Invoice', $html);
        $this->assertStringContainsString($first->number, $html);
        $this->assertStringContainsString(ProformaInvoice::DEFAULT_DESCRIPTION, $html);
        $this->assertStringContainsString('As per attached details and quantities in QTN '.$quote->number, $html);
        $this->assertStringContainsString('50% Advance, 50% Before Delivery', $html);
        $this->assertStringContainsString('Within 2 days from Payment', $html);
        $this->assertStringContainsString('50% Advance to be Paid', $html);
        $this->assertStringContainsString("Co's Stamp", $html);
        $this->assertStringContainsString('Please send all remittances', $html);
        $this->assertStringContainsString('137.03', $html);
    }

    public function test_proforma_is_available_for_every_quotation_status(): void
    {
        $quote = $this->makeQuotation();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $quote->update(['status' => 'rejected']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Proforma')
            ->call('openProforma', $quote->id)
            ->set('proformaPercent', '100')
            ->call('createProforma')
            ->assertHasNoErrors();

        $this->assertSame(1, $quote->proformas()->count());
        $this->assertEquals(274.05, (float) $quote->proformas()->first()->grand_total);
    }

    private function makeQuotation(): Quotation
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();
        $product = Product::where('code', 'TAP-001')->firstOrFail();

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->set('client_query', $client->name)
            ->call('pickProduct', 0, $product->id)
            ->set('lines.0.qty', 2)
            ->set('lines.0.discount_percent', 10)
            ->call('save')
            ->assertHasNoErrors();

        return Quotation::firstOrFail();
    }
}
