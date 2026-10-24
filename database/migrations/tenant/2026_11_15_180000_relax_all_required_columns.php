<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = ['orders', 'order_items', 'payments'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $columns = DB::select(
                "SELECT COLUMN_NAME AS name, COLUMN_TYPE AS type
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND IS_NULLABLE = 'NO'
                   AND COLUMN_DEFAULT IS NULL
                   AND EXTRA NOT LIKE '%auto_increment%'",
                [$table]
            );

            foreach ($columns as $c) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$c->name}` {$c->type} NULL");
            }
        }
    }

    public function down(): void
    {
        // Left empty: reverting would reintroduce NOT NULL on live data.
    }
};