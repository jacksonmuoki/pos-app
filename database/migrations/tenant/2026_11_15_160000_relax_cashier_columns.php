<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The cashier creates orders without a receipt number; receipts show RCP-{id} instead.
        if (Schema::hasColumn('orders', 'receipt_no')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('receipt_no')->nullable()->change();
            });
        }

        // Legacy order_items columns the cashier no longer fills.
        Schema::table('order_items', function (Blueprint $table) {
            foreach ([
                'product_name' => fn () => $table->string('product_name')->nullable()->change(),
                'unit_price'   => fn () => $table->decimal('unit_price', 12, 2)->nullable()->change(),
                'quantity'     => fn () => $table->unsignedInteger('quantity')->nullable()->change(),
                'line_total'   => fn () => $table->decimal('line_total', 12, 2)->nullable()->change(),
            ] as $column => $fix) {
                if (Schema::hasColumn('order_items', $column)) {
                    $fix();
                }
            }
        });
    }

    public function down(): void
    {
        // Left empty: reverting would reintroduce NOT NULL on live data.
    }
};