<?php

use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProformaPdfController;
use App\Http\Controllers\QuotationPdfController;
use App\Livewire\Auth\Login;
use App\Livewire\Clients\ClientForm;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Products\ProductForm;
use App\Livewire\Quotations\Index as QuotationsIndex;
use App\Livewire\Quotations\QuotationEditor;
use App\Livewire\Settings\CompanyForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/media/{path}', MediaController::class)
        ->where('path', '.*')
        ->name('media.show');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/products', ProductsIndex::class)->name('products.index');
    Route::get('/products/create', ProductForm::class)->name('products.create');
    Route::get('/products/{product}/edit', ProductForm::class)->name('products.edit');

    Route::get('/clients', ClientsIndex::class)->name('clients.index');
    Route::get('/clients/create', ClientForm::class)->name('clients.create');
    Route::get('/clients/{client}/edit', ClientForm::class)->name('clients.edit');

    Route::get('/quotations', QuotationsIndex::class)->name('quotations.index');
    Route::get('/quotations/create', QuotationEditor::class)->name('quotations.create');
    Route::get('/quotations/{quotation}/edit', QuotationEditor::class)->name('quotations.edit');
    Route::get('/quotations/{quotation}/pdf', QuotationPdfController::class)->name('quotations.pdf');
    Route::get('/quotations/{quotation}/invoice', InvoicePdfController::class)->name('quotations.invoice');
    Route::get('/quotations/{quotation}/proformas/{proforma}', ProformaPdfController::class)->name('quotations.proforma');

    Route::get('/settings', CompanyForm::class)->name('settings.company');
});
