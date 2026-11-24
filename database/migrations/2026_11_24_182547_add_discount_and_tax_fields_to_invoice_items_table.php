<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('discount_amount', 12, 2)->default(0)->after('unit_price');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('discount_amount');
            $table->decimal('line_total', 12, 2)->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'discount_amount',
                'tax_rate',
                'line_total',
            ]);
        });
    }
};