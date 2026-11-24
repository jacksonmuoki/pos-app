<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // Company using the subscription
            $table->string('tenant_id');

            // Selected SaaS plan
            $table->foreignId('plan_id')
                ->constrained('plans')
                ->restrictOnDelete();

            // Billing information
            $table->string('billing_cycle')->default('monthly');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('KES');

            // Subscription status
            $table->string('status')->default('pending');

            // Subscription dates
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Renewal settings
            $table->boolean('auto_renew')->default(false);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};