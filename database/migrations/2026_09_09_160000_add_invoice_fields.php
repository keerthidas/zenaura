<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('invoice_prefix', 20)->default('ZS')->after('quote_prefix');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->unique()->after('status');
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->unsignedSmallInteger('invoice_sequence_year')->nullable()->after('invoice_date');
            $table->unsignedTinyInteger('invoice_sequence_month')->nullable()->after('invoice_sequence_year');
            $table->unsignedInteger('invoice_sequence_number')->nullable()->after('invoice_sequence_month');

            $table->index(['invoice_sequence_year', 'invoice_sequence_month', 'invoice_sequence_number'], 'quotations_invoice_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex('quotations_invoice_sequence_index');
            $table->dropColumn([
                'invoice_number',
                'invoice_date',
                'invoice_sequence_year',
                'invoice_sequence_month',
                'invoice_sequence_number',
            ]);
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('invoice_prefix');
        });
    }
};
