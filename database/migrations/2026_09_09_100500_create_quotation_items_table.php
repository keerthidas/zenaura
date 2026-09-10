<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('unit', 30)->default('pcs');
            $table->decimal('qty', 12, 3)->default(1);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('discount_percent', 6, 2)->default(0);
            $table->decimal('vat_percent', 6, 2)->default(5);
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
