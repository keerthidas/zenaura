<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('base_number');
            $table->unsignedSmallInteger('revision')->nullable();
            $table->unsignedSmallInteger('sequence_year')->nullable();
            $table->unsignedTinyInteger('sequence_month')->nullable();
            $table->unsignedInteger('sequence_number')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->date('quote_date');
            $table->date('valid_until')->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('vat_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sequence_year', 'sequence_month', 'sequence_number']);
            $table->index('base_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
