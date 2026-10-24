<?php

// Place in: database/migrations/tenant/
// Then run: php artisan tenants:migrate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payments', 'payment_method')) {
            return;
        }

        if (Schema::hasColumn('payments', 'method')) {
            // Legacy column from the original PHP app: keep its data, just rename it.
            Schema::table('payments', function (Blueprint $table) {
                $table->renameColumn('method', 'payment_method');
            });
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('payment_method', 20)->nullable()->after('order_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'payment_method') && ! Schema::hasColumn('payments', 'method')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->renameColumn('payment_method', 'method');
            });
        }
    }
};