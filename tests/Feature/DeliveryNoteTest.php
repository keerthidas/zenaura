<?php

namespace Tests\Feature;

use App\Livewire\DeliveryNotes\Create;
use App\Livewire\DeliveryNotes\Index;
use App\Models\Client;
use App\Models\DeliveryNote;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_notes_can_be_created_in_parts_and_warn_without_blocking_excess(): void
    {
        $user = User::factory()->create();
        $quotation = $this->makeQuotation();

        $first = Livewire::actingAs($user)
            ->test(Create::class)
            ->set('quotationId', (string) $quotation->id)
            ->set('showNewSupplier', true)
            ->set('ns_name', 'Fresh Supplier')
            ->call('saveNewSupplier')
            ->assertHasNoErrors()
            ->assertSet('supplier_id', fn ($id) => Supplier::query()->whereKey($id)->exists())
            ->set('lines.0.delivered_qty', '4')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('delivery-notes.index'));

        $this->assertSame(1, DeliveryNote::count());
        $firstNote = DeliveryNote::with('items')->firstOrFail();
        $this->assertSame('ZN/DN/'.now()->format('my').'/001', $firstNote->number);
        $this->assertEquals(10, (float) $firstNote->items[0]->quoted_qty);
        $this->assertEquals(0, (float) $firstNote->items[0]->delivered_before);
        $this->assertEquals(4, (float) $firstNote->items[0]->delivered_qty);

        $second = Livewire::actingAs($user)
            ->withQueryParams(['quotation' => $quotation->id])
            ->test(Create::class)
            ->assertSet('lines.0.remaining_qty', 6)
            ->set('supplier_id', (string) $firstNote->supplier_id)
            ->set('lines.0.delivered_qty', '8')
            ->assertSee('Over by 2')
            ->assertSee('You can still save this note.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('delivery-notes.index'));

        $this->assertSame(2, DeliveryNote::count());
        $secondNote = DeliveryNote::with('items')->orderByDesc('id')->firstOrFail();
        $this->assertEquals(4, (float) $secondNote->items[0]->delivered_before);
        $this->assertEquals(8, (float) $secondNote->items[0]->delivered_qty);

        $this->actingAs($user)->get(route('delivery-notes.index'))->assertOk()->assertSee($firstNote->number);
        $pdf = $this->actingAs($user)->get(route('delivery-notes.pdf', $secondNote));
        $pdf->assertOk();
        $html = view('pdf.delivery-note', [
            'deliveryNote' => $secondNote->load(['quotation.client', 'supplier', 'items']),
            'settings' => \App\Models\CompanySetting::current(),
            'logoDataUri' => null,
        ])->render();
        $this->assertStringContainsString('DELIVERY NOTE', $html);
        $this->assertStringContainsString('Sales Order No.', $html);
        $this->assertStringContainsString('Tax Invoice No.', $html);
        $this->assertStringContainsString('Currency', $html);
        $this->assertStringContainsString('Customer VAT No.', $html);
        $this->assertStringContainsString('Article No.', $html);
        $this->assertStringContainsString('Description', $html);
        $this->assertStringContainsString('Qty', $html);
        $this->assertStringContainsString('Unit', $html);
        $this->assertStringContainsString('Delivery Confirmation', $html);
        $this->assertStringContainsString('The above goods have been delivered in accordance with the referenced sales order.', $html);
        $this->assertStringContainsString('For Zenaura Sanitary Trading', $html);
        $this->assertStringContainsString('Received by Customer', $html);
        $this->assertStringNotContainsString('Previously delivered', $html);
        $this->assertStringNotContainsString('Delivered now', $html);
        $this->assertStringNotContainsString('Balance', $html);
        $this->assertStringNotContainsString('Quoted', $html);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Delete delivery note')
            ->call('delete', $secondNote->id)
            ->assertDontSee($secondNote->number);

        $this->assertDatabaseMissing('delivery_notes', ['id' => $secondNote->id]);
        $this->assertDatabaseMissing('delivery_note_items', ['delivery_note_id' => $secondNote->id]);
        Livewire::actingAs($user)
            ->withQueryParams(['quotation' => $quotation->id])
            ->test(Create::class)
            ->assertSet('lines.0.remaining_qty', 6);
    }

    public function test_delivery_note_routes_are_linked_from_the_quotation_list(): void
    {
        $user = User::factory()->create();
        $quotation = $this->makeQuotation();

        $this->withoutVite()->actingAs($user)
            ->get(route('quotations.index'))
            ->assertOk()
            ->assertSee(route('delivery-notes.index'))
            ->assertSee(route('delivery-notes.create', ['quotation' => $quotation->id]));
    }

    public function test_only_accepted_quotations_can_be_used_for_delivery_notes(): void
    {
        $user = User::factory()->create();
        $quotation = $this->makeQuotation();
        $quotation->update(['status' => 'rejected']);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('quotationId', (string) $quotation->id)
            ->set('supplier_id', (string) Supplier::create(['name' => 'Test Supplier'])->id)
            ->set('deliveryDate', now()->toDateString())
            ->call('save')
            ->assertHasErrors(['quotationId']);

        $this->assertSame(0, DeliveryNote::count());
        $this->actingAs($user)
            ->get(route('quotations.index'))
            ->assertOk()
            ->assertDontSee(route('delivery-notes.create', ['quotation' => $quotation->id]));
    }

    private function makeQuotation(): Quotation
    {
        $client = Client::create(['name' => 'Test Customer', 'emirate' => 'Dubai']);
        $product = Product::create([
            'code' => 'TEST-DN-001',
            'description' => 'Test mixer',
            'actual_price' => 100,
            'selling_price' => 120,
            'unit' => 'EACH',
        ]);

        $quotation = Quotation::create([
            'number' => 'QT-DN-001',
            'base_number' => 'QT-DN-001',
            'client_id' => $client->id,
            'quote_date' => now()->toDateString(),
            'status' => 'accepted',
            'created_by' => User::query()->value('id'),
        ]);

        $quotation->items()->create([
            'product_id' => $product->id,
            'code' => $product->code,
            'description' => $product->description,
            'unit' => $product->unit,
            'qty' => 10,
            'selling_price' => 120,
            'sort_order' => 1,
        ]);

        return $quotation->load('items');
    }
}