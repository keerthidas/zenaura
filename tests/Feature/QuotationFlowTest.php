<?php

namespace Tests\Feature;

use App\Livewire\Quotations\QuotationEditor;
use App\Models\Client;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class QuotationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_save_a_quotation_with_inline_products(): void
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
            ->set('lines.0.heading', 'WC CISTERN')
            ->set('lines.0.qty', 2)
            ->set('lines.0.discount_percent', 10)
            ->call('save')
            ->assertHasNoErrors();

        $quote = Quotation::first();
        $this->assertNotNull($quote);
        $this->assertSame(1, $quote->items()->count());
        $this->assertSame('WC CISTERN', $quote->items()->first()->heading);
        $this->assertStringStartsWith('ZN-'.now()->format('Y-m').'-', $quote->number);
        $this->assertEquals(290.00, (float) $quote->subtotal);
        $this->assertEquals(29.00, (float) $quote->discount_total);
        $this->assertEquals(13.05, (float) $quote->vat_total);
        $this->assertEquals(274.05, (float) $quote->grand_total);

        $this->actingAs($user)
            ->get(route('quotations.pdf', $quote))
            ->assertOk();

        $pdfHtml = view('pdf.quotation', [
            'quotation' => $quote->load(['client', 'items.product.images']),
            'settings' => \App\Models\CompanySetting::current(),
            'logoDataUri' => null,
            'itemImages' => [],
        ])->render();

        $this->assertStringContainsString('Disc %', $pdfHtml);
        $this->assertStringContainsString('Discount', $pdfHtml);
        $this->assertStringContainsString('10.00', $pdfHtml);
        $this->assertStringContainsString('29.00', $pdfHtml);
        $this->assertStringContainsString('+971 54 783 4673', $pdfHtml);
        $this->assertStringContainsString('zenauratrading@outlook.com', $pdfHtml);
        $this->assertStringContainsString('26th Floor, Amber Gem Tower, Ajman', $pdfHtml);
        $this->assertStringContainsString('Quotation', $pdfHtml);
        $this->assertStringNotContainsString('Salesperson', $pdfHtml);
        $this->assertStringNotContainsString('Sales admin', $pdfHtml);
    }

    public function test_heading_is_optional_when_saving(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();
        $product = Product::where('code', 'TAP-001')->firstOrFail();

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->call('pickProduct', 0, $product->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Quotation::first()->items()->first()->heading);
    }

    public function test_blank_heading_continues_the_previous_section(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();
        $first = Product::where('code', 'TAP-001')->firstOrFail();
        $second = Product::where('code', 'TAP-014')->firstOrFail();

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->call('pickProduct', 0, $first->id)
            ->set('lines.0.heading', 'WC CISTERN')
            ->call('addLine')
            ->call('pickProduct', 1, $second->id)
            ->assertSet('lines.1.heading', '')
            ->call('save')
            ->assertHasNoErrors();

        $quote = Quotation::first()->load('items');
        $groups = $quote->groupedForPdf();

        $this->assertCount(1, $groups);
        $this->assertSame('WC CISTERN', $groups[0]['heading']);
        $this->assertCount(2, $groups[0]['subs'][0]['items']);
        $this->assertNull($quote->items[1]->heading);
    }

    public function test_unknown_codes_are_added_to_the_catalogue_when_the_quote_is_saved(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->set('client_query', $client->name)
            ->set('lines.0.code', 'BS-124')
            ->set('lines.0.description', 'Basin mixer')
            ->set('lines.0.selling_price', '52')
            ->set('lines.0.unit', 'PCS')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('code', 'BS-124')->first();
        $this->assertNotNull($product);
        $this->assertSame('Basin mixer', $product->description);
        $this->assertEquals(52.00, (float) $product->selling_price);
        $this->assertSame($product->id, Quotation::first()->items()->first()->product_id);
    }

    public function test_revise_does_not_create_a_record_until_saved(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();
        $product = Product::where('code', 'TAP-001')->firstOrFail();

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->call('pickProduct', 0, $product->id)
            ->call('save')
            ->assertHasNoErrors();

        $original = Quotation::first();
        $this->assertSame(1, Quotation::count());

        Livewire::actingAs($user)
            ->test(\App\Livewire\Quotations\Index::class)
            ->call('revise', $original->id)
            ->assertRedirect(route('quotations.create', ['revise' => $original->id]));

        $this->assertSame(1, Quotation::count());

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->call('startRevision', $original->id)
            ->assertSet('revisingFromId', $original->id)
            ->assertSet('number', $original->base_number.'-1R')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Quotation::count());
        $revision = Quotation::query()->where('parent_id', $original->id)->first();
        $this->assertNotNull($revision);
        $this->assertSame($original->base_number.'-1R', $revision->number);
        $this->assertSame(1, (int) $revision->revision);
    }

    public function test_status_can_be_updated_from_the_list(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();
        $product = Product::where('code', 'TAP-001')->firstOrFail();

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->call('pickProduct', 0, $product->id)
            ->call('save')
            ->assertHasNoErrors();

        $quote = Quotation::first();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Quotations\Index::class)
            ->call('updateStatus', $quote->id, 'sent')
            ->assertHasNoErrors();

        $this->assertSame('sent', $quote->fresh()->status);
    }

    public function test_accepting_a_quotation_assigns_an_invoice_and_opens_the_pdf(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();
        $product = Product::where('code', 'TAP-001')->firstOrFail();

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->call('pickProduct', 0, $product->id)
            ->call('save')
            ->assertHasNoErrors();

        $quote = Quotation::first();

        $this->actingAs($user)
            ->get(route('quotations.invoice', $quote))
            ->assertNotFound();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Quotations\Index::class)
            ->call('updateStatus', $quote->id, 'accepted')
            ->assertHasNoErrors();

        $quote = $quote->fresh();
        $this->assertSame('accepted', $quote->status);
        $this->assertNotNull($quote->invoice_number);
        $this->assertStringStartsWith('ZS/TI/'.now()->format('my').'/', $quote->invoice_number);

        $invoiceNumber = $quote->invoice_number;

        Livewire::actingAs($user)
            ->test(\App\Livewire\Quotations\Index::class)
            ->call('updateStatus', $quote->id, 'accepted')
            ->assertHasNoErrors();

        $this->assertSame($invoiceNumber, $quote->fresh()->invoice_number);

        $this->actingAs($user)
            ->get(route('quotations.invoice', $quote))
            ->assertOk();

        $html = view('pdf.invoice', [
            'quotation' => $quote->load(['client', 'items']),
            'settings' => \App\Models\CompanySetting::current(),
            'logoDataUri' => null,
        ])->render();

        $this->assertStringContainsString('Tax Invoice', $html);
        $this->assertStringNotContainsString('Consolidated', $html);
        $this->assertStringContainsString('+971 54 783 4673', $html);
        $this->assertStringContainsString('zenauratrading@outlook.com', $html);
        $this->assertStringContainsString('26th Floor, Amber Gem Tower, Ajman', $html);
        $this->assertStringContainsString('Invoiced To:', $html);
        $this->assertStringContainsString('Shipped To:', $html);
        $this->assertStringContainsString($invoiceNumber, $html);
        $this->assertStringContainsString('Net Amount', $html);
        $this->assertStringContainsString('<td class="cur">AED</td>', $html);
        $this->assertStringContainsString('system generated invoice', $html);
        $this->assertStringContainsString('Signature', $html);
        $this->assertStringContainsString("Co's Stamp", $html);
        $this->assertStringNotContainsString('Salesperson', $html);
        $this->assertStringNotContainsString('Sales admin', $html);
        $this->assertStringNotContainsString('This is a quotation, not a tax invoice', $html);
        $this->assertStringContainsString($product->code, $html);
        $this->assertStringContainsString($product->description, $html);
    }

    public function test_staff_can_upload_a_line_image_from_the_quotation(): void
    {
        $this->seed();
        Storage::fake('public');
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();
        $product = Product::where('code', 'TAP-001')->firstOrFail();
        $file = UploadedFile::fake()->image('tap.jpg', 80, 80);

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->call('pickProduct', 0, $product->id)
            ->set('linePhotos.0', $file)
            ->call('save')
            ->assertHasNoErrors();

        $item = Quotation::first()->items()->first();
        $this->assertNotNull($item->image_path);
        Storage::disk('public')->assertExists($item->image_path);
    }
}
