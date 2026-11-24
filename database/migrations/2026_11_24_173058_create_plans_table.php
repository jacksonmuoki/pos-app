
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            // Basic plan information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('quarterly_price', 12, 2)->default(0);
            $table->decimal('annual_price', 12, 2)->default(0);
            $table->string('currency', 3)->default('KES');

            // Plan limits
            $table->unsignedInteger('max_users')->default(1);
            $table->unsignedInteger('max_branches')->default(1);
            $table->unsignedInteger('max_products')->default(100);
            $table->unsignedInteger('storage_limit_mb')->default(500);

            // Trial configuration
            $table->unsignedInteger('trial_days')->default(0);

            // Plan status and display settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // Additional configuration
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
