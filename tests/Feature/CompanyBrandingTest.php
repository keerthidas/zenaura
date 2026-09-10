<?php

namespace Tests\Feature;

use App\Livewire\Settings\CompanyForm;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_brand_falls_back_to_the_default_public_image(): void
    {
        $uri = pdf_brand_data_uri(null, 'images/logo.png');

        $this->assertNotNull($uri);
        $this->assertStringStartsWith('data:image/', $uri);
    }

    public function test_pdf_brand_prefers_an_uploaded_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('company/custom.png', 'custom-bytes');

        $uri = pdf_brand_data_uri('company/custom.png', 'images/logo.png');

        $this->assertNotNull($uri);
        $this->assertStringContainsString(base64_encode('custom-bytes'), $uri);
    }

    public function test_staff_can_upload_signature_and_stamp_from_settings(): void
    {
        $this->seed();
        Storage::fake('public');
        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();

        Livewire::actingAs($user)
            ->test(CompanyForm::class)
            ->assertSee('Signature')
            ->assertSee('Using default signature')
            ->set('signature', UploadedFile::fake()->image('sign.png', 80, 40))
            ->set('stamp', UploadedFile::fake()->image('stamp.png', 120, 120))
            ->call('save')
            ->assertHasNoErrors();

        $settings = CompanySetting::query()->firstOrFail();
        $this->assertNotNull($settings->signature_path);
        $this->assertNotNull($settings->stamp_path);
        Storage::disk('public')->assertExists($settings->signature_path);
        Storage::disk('public')->assertExists($settings->stamp_path);
    }
}
