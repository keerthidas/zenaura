<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_core_pages(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();

        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk()->assertSee('Quotations');
        $this->get(route('products.index'))->assertOk()->assertSee('TAP-001');
        $this->get(route('products.create'))->assertOk()->assertSee('New product');
        $this->get(route('clients.index'))->assertOk()->assertSee('Al Noor');
        $this->get(route('clients.create'))->assertOk()->assertSee('TRN is required');
        $this->get(route('quotations.index'))->assertOk()->assertSee('Quotations');
        $this->get(route('quotations.create'))->assertOk()->assertSee('Line items')->assertSee('Open menu');
        $this->get(route('settings.company'))
            ->assertOk()
            ->assertSee('Company settings')
            ->assertSee('Signature')
            ->assertSee('Using default stamp');
    }

    public function test_staff_can_view_a_stored_product_image(): void
    {
        $this->seed();
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();

        Storage::fake('public');
        Storage::disk('public')->put('products/1/test-photo.jpg', 'fake-jpeg-bytes');

        $this->actingAs($user)
            ->get('/media/products/1/test-photo.jpg')
            ->assertOk();
    }
}
