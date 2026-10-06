<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->date('delivery_date');
            $table->unsignedSmallInteger('sequence_year');
            $table->unsignedTinyInteger('sequence_month');
            $table->unsignedInteger('sequence_number');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sequence_year', 'sequence_month', 'sequence_number'], 'delivery_notes_sequence_index');
            $table->index(['quotation_id', 'delivery_date']);
        });

        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable();
            $table->text('description');
            $table->string('unit', 30)->nullable();
            $table->decimal('quoted_qty', 12, 3)->default(0);
            $table->decimal('delivered_before', 12, 3)->default(0);
            $table->decimal('delivered_qty', 12, 3)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
    }
};