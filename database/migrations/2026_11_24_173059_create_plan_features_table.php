
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
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();

            // Link feature to a subscription plan
            $table->foreignId('plan_id')
                ->constrained('plans')
                ->cascadeOnDelete();

            // Feature information
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            // Feature configuration
            $table->boolean('is_enabled')->default(true);
            $table->string('value')->nullable();

            // Display settings
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Prevent duplicate features within the same plan
            $table->unique(['plan_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
