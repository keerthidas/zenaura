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
        $this->assertGreaterThan(strpos($pdfHtml, '>Qty<'), strpos($pdfHtml, 'Sales Price'));
        $this->assertGreaterThan(strpos($pdfHtml, 'Sales Price'), strpos($pdfHtml, 'Disc %'));
        $this->assertGreaterThan(strpos($pdfHtml, 'Disc %'), strpos($pdfHtml, 'Net sales'));
        $this->assertStringContainsString('130.50', $pdfHtml);
        $this->assertStringContainsString('Discount', $pdfHtml);
        $this->assertStringContainsString('10.00', $pdfHtml);
        $this->assertStringContainsString('29.00', $pdfHtml);
        $settings = \App\Models\CompanySetting::current();
        $this->assertStringContainsString($settings->phone, $pdfHtml);
        $this->assertStringContainsString($settings->email, $pdfHtml);
        $this->assertStringContainsString($settings->address, $pdfHtml);
        $this->assertStringContainsString('TAP-001', $pdfHtml);
        $this->assertStringContainsString('CHROME BASIN MIXER, SINGLE LEVER', $pdfHtml);
        $this->assertStringNotContainsString('Chrome basin mixer, single lever', $pdfHtml);
        $this->assertStringContainsString('Quotation', $pdfHtml);
        $this->assertStringContainsString('margin: 88px 36px 62px 36px', $pdfHtml);
        $this->assertStringContainsString('@page :first { margin-top: 76px; }', $pdfHtml);
        $this->assertStringContainsString('table-header-group', $pdfHtml);
        $this->assertStringNotContainsString('Salesperson', $pdfHtml);
        $this->assertStringNotContainsString('Sales admin', $pdfHtml);
    }

    public function test_bulk_discount_fills_every_line_and_leaves_rows_editable(): void
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
            ->call('addLine')
            ->call('pickProduct', 1, $second->id)
            ->set('bulk_discount', '10')
            ->call('applyDiscountToAll')
            ->assertHasNoErrors()
            ->assertSet('lines.0.discount_percent', '10')
            ->assertSet('lines.1.discount_percent', '10')
            ->set('lines.1.discount_percent', '5')
            ->assertSet('lines.0.discount_percent', '10')
            ->assertSet('lines.1.discount_percent', '5');
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

    public function test_editing_a_line_description_updates_the_catalogue_product(): void
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
            ->set('lines.0.description', 'Updated basin mixer description')
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $item = Quotation::first()->items()->first();

        $this->assertSame('Updated basin mixer description', $product->description);
        $this->assertSame('Updated basin mixer description', $item->description);
    }

    public function test_thousands_separators_in_selling_price_are_stored_as_decimals(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->set('client_query', $client->name)
            ->set('lines.0.code', 'BDS-K133-A02-ESG5')
            ->set('lines.0.description', 'Concealed cistern')
            ->set('lines.0.qty', '6')
            ->set('lines.0.selling_price', '1,114.29')
            ->set('lines.0.unit', 'PCS')
            ->call('save')
            ->assertHasNoErrors();

        $item = Quotation::first()->items()->first();
        $this->assertEquals(1114.29, (float) $item->selling_price);
        $this->assertEquals(6685.74, (float) $item->amount);
        $this->assertEquals(334.29, (float) $item->vat_amount);
        $this->assertEquals(7020.03, (float) $item->line_total);
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
        $this->assertSame('draft', $original->status);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Quotations\Index::class)
            ->assertSee('Edit')
            ->assertDontSee('Revise')
            ->call('revise', $original->id)
            ->assertRedirect(route('quotations.edit', $original));

        $this->assertSame(1, Quotation::count());
        $this->assertSame($original->number, $original->fresh()->number);

        Livewire::actingAs($user)
            ->test(QuotationEditor::class, ['quotation' => $original])
            ->set('notes', 'Still the same draft')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Quotation::count());
        $this->assertSame($original->number, Quotation::first()->number);
        $this->assertNull(Quotation::first()->revision);

        $original->update(['status' => 'sent']);

        Livewire::actingAs($user)
            ->test(QuotationEditor::class, ['quotation' => $original->fresh()])
            ->assertRedirect(route('quotations.create', ['revise' => $original->id]));

        Livewire::actingAs($user)
            ->test(\App\Livewire\Quotations\Index::class)
            ->assertSee('Revise')
            ->assertDontSee('Edit')
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

        Livewire::actingAs($user)
            ->test(\App\Livewire\Quotations\Index::class)
            ->call('delete', $revision->id);

        $this->assertSoftDeleted($revision);
        $this->assertSame(1, Quotation::count());

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->call('startRevision', $original->id)
            ->assertSet('revisingFromId', $original->id)
            ->assertSet('number', $original->base_number.'-2R')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Quotation::count());
        $next = Quotation::query()->where('parent_id', $original->id)->first();
        $this->assertNotNull($next);
        $this->assertSame($original->base_number.'-2R', $next->number);
        $this->assertSame(2, (int) $next->revision);
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
        $settings = \App\Models\CompanySetting::current();
        $this->assertStringContainsString($settings->phone, $html);
        $this->assertStringContainsString($settings->email, $html);
        $this->assertStringContainsString($settings->address, $html);
        $this->assertStringContainsString('Invoiced To:', $html);
        $this->assertStringContainsString('Shipped To:', $html);
        $this->assertStringContainsString($invoiceNumber, $html);
        $this->assertStringContainsString('Net Amount', $html);
        $this->assertStringContainsString('<td class="cur">AED</td>', $html);
        $this->assertStringContainsString('Please send all remittances', $html);
        $this->assertStringContainsString('Signature', $html);
        $this->assertStringContainsString("Co's Stamp", $html);
        $this->assertStringNotContainsString('Salesperson', $html);
        $this->assertStringNotContainsString('Sales admin', $html);
        $this->assertStringNotContainsString('This is a quotation, not a tax invoice', $html);
        $this->assertStringContainsString($product->code, $html);
        $this->assertStringContainsString($product->description, $html);
        $this->assertGreaterThan(strpos($html, '>Qty<'), strpos($html, 'Sales price'));
        $this->assertGreaterThan(strpos($html, 'Sales price'), strpos($html, 'Discount%'));
        $this->assertGreaterThan(strpos($html, 'Discount%'), strpos($html, 'Net sales'));

        $line = $quote->items->first();
        $netSalesPrice = round((float) $line->selling_price * (1 - ((float) $line->discount_percent / 100)), 2);
        $this->assertStringContainsString(number_format($netSalesPrice, 2), $html);
    }

    public function test_staff_can_upload_a_line_image_from_the_quotation(): void
    {
        $this->seed();
        Storage::fake('public');
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();
        $product = Product::where('code', 'TAP-001')->firstOrFail();
        $oldPath = 'products/'.$product->id.'/old.png';
        Storage::disk('public')->put($oldPath, 'old-image');
        $product->images()->create(['path' => $oldPath, 'sort_order' => 1]);
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

        $catalogue = $product->fresh()->images()->first();
        $this->assertNotNull($catalogue);
        $this->assertNotSame($oldPath, $catalogue->path);
        Storage::disk('public')->assertExists($catalogue->path);
        Storage::disk('public')->assertMissing($oldPath);

        $replacement = UploadedFile::fake()->image('tap-new.jpg', 80, 80);
        $quote = Quotation::first();

        Livewire::actingAs($user)
            ->test(QuotationEditor::class, ['quotation' => $quote])
            ->set('linePhotos.0', $replacement)
            ->call('save')
            ->assertHasNoErrors();

        $updated = $product->fresh()->images()->first();
        $this->assertNotSame($catalogue->path, $updated->path);
        Storage::disk('public')->assertExists($updated->path);
        Storage::disk('public')->assertMissing($catalogue->path);
    }
}
