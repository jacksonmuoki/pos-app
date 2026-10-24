<?php

// Place in: database/migrations/tenant/
// Only this one inventory migration should exist in that folder.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('address')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->string('reference')->unique();
                $table->foreignId('supplier_id')->constrained();
                $table->foreignId('user_id')->constrained();
                $table->date('purchase_date');
                $table->decimal('total', 12, 2)->default(0);
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_cost', 12, 2);
                $table->decimal('line_total', 12, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_adjustments')) {
            Schema::create('stock_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained();
                $table->foreignId('user_id')->constrained();
                $table->integer('change');
                $table->integer('stock_before');
                $table->integer('stock_after');
                $table->string('reason');
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};