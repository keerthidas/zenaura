<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Zenaura');
            $table->string('address')->nullable();
            $table->string('emirate')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('trn')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('quote_prefix', 20)->default('ZN');
            $table->decimal('default_vat_percent', 5, 2)->default(5);
            $table->unsignedSmallInteger('default_validity_days')->default(30);
            $table->string('default_unit', 30)->default('pcs');
            $table->string('currency', 10)->default('AED');
            $table->text('default_terms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
