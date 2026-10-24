<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (! Schema::hasColumn('orders', 'table_no'))       $t->string('table_no', 20)->nullable();
            if (! Schema::hasColumn('orders', 'discount'))       $t->decimal('discount', 12, 2)->default(0);
            if (! Schema::hasColumn('orders', 'change_due'))     $t->decimal('change_due', 12, 2)->default(0);
            if (! Schema::hasColumn('orders', 'paid_at'))        $t->dateTime('paid_at')->nullable();
            if (! Schema::hasColumn('orders', 'payment_method')) $t->string('payment_method', 20)->nullable();
        });

        Schema::table('order_items', function (Blueprint $t) {
            if (! Schema::hasColumn('order_items', 'name'))  $t->string('name');
            if (! Schema::hasColumn('order_items', 'price')) $t->decimal('price', 12, 2)->default(0);
            if (! Schema::hasColumn('order_items', 'qty'))   $t->unsignedInteger('qty')->default(1);
        });

        Schema::table('payments', function (Blueprint $t) {
            if (! Schema::hasColumn('payments', 'payment_method'))  $t->string('payment_method', 20)->nullable();
            if (! Schema::hasColumn('payments', 'mpesa_receipt'))   $t->string('mpesa_receipt', 60)->nullable();
            if (! Schema::hasColumn('payments', 'response_message')) $t->string('response_message')->nullable();
            if (! Schema::hasColumn('payments', 'external_reference')) $t->string('external_reference', 80)->nullable()->index();
        });
    }

    public function down(): void
    {
        // Left intentionally empty: these columns hold live sales data.
    }
};