<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            return;
        }

        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            // Branch identification
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_main')->default(false);

            // Contact information
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();

            // Physical location
            $table->string('country')->default('Kenya');
            $table->string('county')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code', 20)->nullable();

            // Operational information
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_main']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
