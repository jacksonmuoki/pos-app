<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'purchase_date')) {
                $table->date('purchase_date')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('purchases', 'total')) {
                $table->decimal('total', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('purchases', 'notes')) {
                $table->string('notes')->nullable();
            }
            if (! Schema::hasColumn('purchases', 'reference')) {
                $table->string('reference')->nullable();
            }
        });

        // Backfill existing rows so the dashboard totals include them.
        if (Schema::hasColumn('purchases', 'created_at')) {
            DB::table('purchases')->whereNull('purchase_date')
                ->update(['purchase_date' => DB::raw('DATE(created_at)')]);
        }
    }

    public function down(): void
    {
        // Left empty: the columns hold live data.
    }
};