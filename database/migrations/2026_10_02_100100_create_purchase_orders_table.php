<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->date('lpo_date');
            $table->unsignedSmallInteger('sequence_year');
            $table->unsignedTinyInteger('sequence_month');
            $table->unsignedInteger('sequence_number');
            $table->string('status', 20)->default('issued');
            $table->string('supplier_quote_number')->nullable();
            $table->date('supplier_quote_date')->nullable();
            $table->string('customer_reference')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('delivery_terms')->nullable();
            $table->string('delivery_availability')->nullable();
            $table->text('buyer_details')->nullable();
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('vat_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->foreignId('replaces_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sequence_year', 'sequence_month', 'sequence_number'], 'purchase_orders_sequence_index');
            $table->index(['quotation_id', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->text('description');
            $table->string('unit', 30)->nullable();
            $table->decimal('qty', 12, 3)->default(1);
            $table->decimal('quoted_price', 14, 2)->default(0);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('discount_percent', 6, 2)->default(0);
            $table->decimal('vat_percent', 6, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
