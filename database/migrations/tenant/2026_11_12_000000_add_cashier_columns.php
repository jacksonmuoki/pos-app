<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'table_no')) {
                $table->string('table_no', 20)->nullable();
            }
            if (!Schema::hasColumn('orders', 'change_due')) {
                $table->decimal('change_due', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
        });

        // Allow the statuses the cashier uses (open, held, paid, cancelled ...)
        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(20) NOT NULL DEFAULT 'open'");
        DB::statement("ALTER TABLE payments MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['table_no', 'change_due', 'paid_at']);
        });
    }
};