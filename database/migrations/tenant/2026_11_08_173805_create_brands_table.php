
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('brands')) {
            return;
        }

        Schema::create('brands', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();

            $table->string('logo_path')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        // Keep existing company data during rollback.
        // Remove the table manually only when intentionally resetting
        // the catalogue database.
    }
};
