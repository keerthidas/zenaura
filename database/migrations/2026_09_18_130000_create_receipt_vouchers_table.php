<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_invoice_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->date('received_date');
            $table->unsignedSmallInteger('sequence_year');
            $table->unsignedTinyInteger('sequence_month');
            $table->unsignedInteger('sequence_number');
            $table->string('payment_mode', 50);
            $table->string('reference')->nullable();
            $table->decimal('amount', 14, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sequence_year', 'sequence_month', 'sequence_number'], 'receipt_vouchers_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_vouchers');
    }
};
