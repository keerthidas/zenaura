<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->date('invoice_date');
            $table->unsignedSmallInteger('sequence_year');
            $table->unsignedTinyInteger('sequence_month');
            $table->unsignedInteger('sequence_number');
            $table->decimal('percent', 6, 2);
            $table->text('description');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('vat_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sequence_year', 'sequence_month', 'sequence_number'], 'proforma_invoices_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};
