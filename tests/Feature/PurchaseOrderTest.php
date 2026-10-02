<?php

namespace Tests\Feature;

use App\Livewire\PurchaseOrders\Index;
use App\Livewire\PurchaseOrders\PurchaseOrderEditor;
use App\Livewire\Quotations\QuotationEditor;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_lpo_uses_edited_unit_price_and_recalculates_totals(): void
    {
        [$quote, $user] = $this->makeQuotation();
        $supplier = $this->makeSupplier('SANIPEX (L.L.C.)');

        $editor = Livewire::actingAs($user)
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->assertSet('lines.0.include', true)
            ->assertSet('lines.0.qty', '2')
            ->assertSet('lines.0.unit_price', '72.5')
            ->set('supplier_id', $supplier->id)
            ->set('supplier_quote_number', 'SFZE-Q-000466791-1')
            ->set('lines.0.qty', '65')
            ->set('lines.0.unit_price', '742.86')
            ->set('lines.0.discount_percent', '30')
            ->assertSet('lines.0.unit_net', '520.00')
            ->assertSet('lines.0.net_amount', '33800.13');

        $this->assertEquals(35490.14, $editor->instance()->totals['grand_total']);

        $editor->call('save')->assertHasNoErrors();

        $order = PurchaseOrder::with('items')->firstOrFail();
        $this->assertSame('ZN/PO/'.now()->format('my').'/001', $order->number);
        $this->assertTrue($order->isIssued());
        $this->assertSame($quote->id, $order->quotation_id);
        $this->assertSame($supplier->id, $order->supplier_id);
        $this->assertEquals(48285.90, (float) $order->subtotal);
        $this->assertEquals(14485.77, (float) $order->discount_total);
        $this->assertEquals(33800.13, $order->netExclVat());
        $this->assertEquals(1690.01, (float) $order->vat_total);
        $this->assertEquals(35490.14, (float) $order->grand_total);
        $this->assertEquals(72.50, (float) $order->items[0]->quoted_price);
        $this->assertEquals(742.86, (float) $order->items[0]->unit_price);

        $this->assertEquals(72.50, (float) $quote->fresh()->items[0]->selling_price, 'Quotation prices must not change.');

        $this->actingAs($user)->get(route('purchase-orders.pdf', $order))->assertOk();

        $html = view('pdf.purchase-order', [
            'order' => $order->load(['quotation', 'supplier', 'items']),
            'settings' => CompanySetting::current(),
            'logoDataUri' => null,
            'signatureDataUri' => null,
            'stampDataUri' => null,
        ])->render();

        $this->assertStringContainsString('LOCAL PURCHASE ORDER', $html);
        $this->assertStringContainsString($order->number, $html);
        $this->assertStringContainsString('SFZE-Q-000466791-1', $html);
        $this->assertStringContainsString('SANIPEX (L.L.C.)', $html);
        $this->assertStringContainsString('742.86', $html);
        $this->assertStringContainsString('520.00', $html);
        $this->assertStringContainsString('33,800.13', $html);
        $this->assertStringContainsString('VAT 5%', $html);
        $this->assertStringContainsString('35,490.14', $html);
        $this->assertStringContainsString('Supply the above item as per quotation SFZE-Q-000466791-1', $html);
        $this->assertStringContainsString('Supplier Acknowledgement', $html);
        $this->assertStringNotContainsString('CANCELLED', $html);
    }

    public function test_quotation_can_be_split_across_multiple_lpos_and_suppliers(): void
    {
        [$quote, $user] = $this->makeQuotation();
        $first = $this->makeSupplier('Supplier One');
        $second = $this->makeSupplier('Supplier Two');

        Livewire::actingAs($user)
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->set('supplier_id', $first->id)
            ->set('lines.0.qty', '1')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->assertSet('lines.0.ordered_elsewhere', 1.0)
            ->assertSet('lines.0.qty', '1')
            ->set('supplier_id', $second->id)
            ->set('lines.0.unit_price', '60')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->assertSet('lines.0.ordered_elsewhere', 2.0)
            ->assertSet('lines.0.include', false)
            ->set('supplier_id', $first->id)
            ->call('save')
            ->assertHasErrors(['lines']);

        $this->assertSame(2, $quote->purchaseOrders()->count());
        $this->assertSame(
            ['ZN/PO/'.now()->format('my').'/001', 'ZN/PO/'.now()->format('my').'/002'],
            $quote->purchaseOrders()->pluck('number')->all()
        );
        $this->assertEquals(60.0, (float) PurchaseOrder::latest('id')->first()->items[0]->unit_price);

        Livewire::actingAs($user)
            ->test(Index::class, ['quotation' => $quote->id])
            ->assertSee('Supplier One')
            ->assertSee('Supplier Two')
            ->assertSee('Active LPOs');
    }

    public function test_lpo_can_be_cancelled_and_reissued_with_new_prices(): void
    {
        [$quote, $user] = $this->makeQuotation();
        $supplier = $this->makeSupplier('Supplier One');

        Livewire::actingAs($user)
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->set('supplier_id', $supplier->id)
            ->set('payment_terms', '30 DAYS CREDIT')
            ->call('save')
            ->assertHasNoErrors();

        $original = PurchaseOrder::firstOrFail();

        $editor = Livewire::actingAs($user)
            ->withQueryParams(['replace' => $original->id])
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->assertSet('replacingId', $original->id)
            ->assertSet('supplier_id', $supplier->id)
            ->assertSet('payment_terms', '30 DAYS CREDIT')
            ->assertSet('lines.0.ordered_elsewhere', 0.0)
            ->assertSet('lines.0.include', true)
            ->set('lines.0.unit_price', '50');

        $this->assertTrue($original->fresh()->isIssued(), 'Original stays active until the replacement is issued.');

        $editor->call('save')->assertHasNoErrors();

        $original->refresh();
        $replacement = PurchaseOrder::query()->where('replaces_id', $original->id)->firstOrFail();

        $this->assertTrue($original->isCancelled());
        $this->assertSame('Replaced by '.$replacement->number, $original->cancel_reason);
        $this->assertNotSame($original->number, $replacement->number);
        $this->assertEquals(94.50, (float) $replacement->grand_total);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('reissue', $original->id)
            ->assertNoRedirect();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('startCancel', $replacement->id)
            ->assertSee('Cancel '.$replacement->number.'?')
            ->set('cancelReason', 'Client put order on hold')
            ->call('confirmCancel')
            ->assertHasNoErrors();

        $replacement->refresh();
        $this->assertTrue($replacement->isCancelled());
        $this->assertSame('Client put order on hold', $replacement->cancel_reason);
        $this->assertSame(0, $quote->purchaseOrders()->issued()->count());

        $html = view('pdf.purchase-order', [
            'order' => $replacement->load(['quotation', 'supplier', 'items']),
            'settings' => CompanySetting::current(),
            'logoDataUri' => null,
            'signatureDataUri' => null,
            'stampDataUri' => null,
        ])->render();
        $this->assertStringContainsString('CANCELLED', $html);

        Livewire::actingAs($user)
            ->withQueryParams(['replace' => $original->id])
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->assertRedirect(route('purchase-orders.index', ['quotation' => $quote->id]));

        Livewire::actingAs($user)
            ->withQueryParams([])
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->assertSet('lines.0.ordered_elsewhere', 0.0)
            ->set('supplier_id', $supplier->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, PurchaseOrder::count());
        $this->assertSame('ZN/PO/'.now()->format('my').'/003', PurchaseOrder::latest('id')->first()->number);
    }

    public function test_lpo_can_be_deleted_from_the_list(): void
    {
        [$quote, $user] = $this->makeQuotation();
        $supplier = $this->makeSupplier('Supplier One');

        Livewire::actingAs($user)
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->set('supplier_id', $supplier->id)
            ->call('save')
            ->assertHasNoErrors();

        $order = PurchaseOrder::firstOrFail();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee($order->number)
            ->call('delete', $order->id)
            ->assertDontSee($order->number);

        $this->assertSame(0, PurchaseOrder::count());
        $this->assertSoftDeleted($order);

        Livewire::actingAs($user)
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->assertSet('lines.0.ordered_elsewhere', 0.0)
            ->assertSet('lines.0.include', true)
            ->set('supplier_id', $supplier->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ZN/PO/'.now()->format('my').'/002', PurchaseOrder::firstOrFail()->number);
    }

    public function test_supplier_can_be_added_from_the_lpo_editor(): void
    {
        [$quote, $user] = $this->makeQuotation();

        Livewire::actingAs($user)
            ->test(PurchaseOrderEditor::class, ['quotation' => $quote])
            ->set('showNewSupplier', true)
            ->set('ns_name', 'Inline Supplier LLC')
            ->set('ns_email', 'sales@inline.test')
            ->call('saveNewSupplier')
            ->assertHasNoErrors()
            ->assertSet('showNewSupplier', false)
            ->assertSet('supplier_id', Supplier::where('name', 'Inline Supplier LLC')->value('id'));
    }

    public function test_supplier_and_lpo_pages_open(): void
    {
        [$quote, $user] = $this->makeQuotation();
        $supplier = $this->makeSupplier('Supplier One');

        $this->withoutVite()->actingAs($user);
        $this->get(route('suppliers.index'))->assertOk()->assertSee('Supplier One');
        $this->get(route('suppliers.create'))->assertOk()->assertSee('New supplier');
        $this->get(route('suppliers.edit', $supplier))->assertOk()->assertSee('Edit supplier');
        $this->get(route('purchase-orders.index'))->assertOk()->assertSee('Local purchase orders');
        $this->get(route('purchase-orders.create', $quote))->assertOk()->assertSee('New LPO')->assertSee($quote->number);
        $this->get(route('quotations.index'))->assertOk()->assertSee('LPO');
    }

    /**
     * @return array{0: Quotation, 1: User}
     */
    private function makeQuotation(): array
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::create(['name' => 'Al Noor Contracting', 'trn' => '100000000000003', 'emirate' => 'Dubai']);
        $product = Product::create([
            'code' => 'TAP-001',
            'description' => 'Basin mixer chrome',
            'actual_price' => 50,
            'selling_price' => 72.50,
            'unit' => 'EACH',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(QuotationEditor::class)
            ->set('client_id', $client->id)
            ->set('client_query', $client->name)
            ->call('pickProduct', 0, $product->id)
            ->set('lines.0.qty', 2)
            ->set('lines.0.discount_percent', 10)
            ->call('save')
            ->assertHasNoErrors();

        return [Quotation::with('items')->firstOrFail(), $user];
    }

    private function makeSupplier(string $name): Supplier
    {
        return Supplier::create([
            'name' => $name,
            'address' => "Bagno Design Building\nSheikh Zayed Road\nPO Box 30871, Dubai, UAE",
            'phone' => '+971 4 507 6000',
            'email' => 'trade@example.com',
            'trn' => '100244993000003',
        ]);
    }
}
