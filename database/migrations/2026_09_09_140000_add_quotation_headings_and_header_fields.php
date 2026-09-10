<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('salesperson')->nullable()->after('reference');
            $table->string('sales_admin')->nullable()->after('salesperson');
            $table->string('delivery')->nullable()->after('sales_admin');
            $table->string('payment_terms')->nullable()->after('delivery');
            $table->string('delivery_availability')->nullable()->after('payment_terms');
            $table->text('delivery_details')->nullable()->after('delivery_availability');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('heading')->nullable()->after('product_id');
            $table->string('placing_area')->nullable()->after('heading');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'salesperson',
                'sales_admin',
                'delivery',
                'payment_terms',
                'delivery_availability',
                'delivery_details',
            ]);
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['heading', 'placing_area']);
        });
    }
};
